<?php
declare(strict_types=1);

namespace Kanboard\Plugin\ModelContextProtocol\Core;

use Traversable;

/**
 * Enforces a TokenScope on tool calls and on the lists the server returns.
 *
 * Every id a call refers to is resolved to its project through the database,
 * and the call is refused unless all of those projects are allowed.
 */
final class ScopeGuard
{
    public const TOOL_BLOCKED = 'tool blocked for this token';
    public const OUT_OF_SCOPE = 'outside token scope';

    /**
     * Argument names that point at a project-owned record.
     */
    private const ID_ARGUMENTS = [
        'project_id',
        'task_id',
        'column_id',
        'swimlane_id',
        'category_id',
        'tag_id',
        'comment_id',
    ];

    /**
     * Tools that legitimately name no project. Their results are filtered to the
     * allowed projects instead (see filterResult). Any other tool that resolves
     * to no project is refused.
     */
    private const PROJECTLESS_TOOLS = ['get_projects', 'get_users', 'get_tags'];

    public function __construct(private $container, private readonly TokenScope $scope)
    {
    }

    /**
     * Decide whether a tool call may run.
     *
     * Returns the arguments with every id normalised to an integer, so the
     * handler acts on exactly the values that were authorised.
     *
     * @param array<string,mixed> $arguments
     * @return array<string,mixed>
     * @throws ScopeViolation
     */
    public function authorize(string $tool, array $arguments): array
    {
        if ($this->scope->isToolBlocked($tool)) {
            throw new ScopeViolation(self::TOOL_BLOCKED);
        }

        if (!$this->scope->isRestricted()) {
            return $arguments;
        }

        $projects = [];

        foreach (self::ID_ARGUMENTS as $key) {
            if (!isset($arguments[$key])) {
                continue;
            }

            $id = self::toId($arguments[$key]);

            if ($id === null) {
                throw new ScopeViolation('Invalid arguments: ' . $key . ' must be a positive integer');
            }

            $arguments[$key] = $id;

            // category_id 0 means "no category" and refers to no record
            if ($key === 'category_id' && $id === 0) {
                continue;
            }

            $projects[] = $this->resolveProject($key, $id);
        }

        if (isset($arguments['column_ids'])) {
            $rawIds = $this->toList($arguments['column_ids']);

            if ($rawIds === null) {
                throw new ScopeViolation('Invalid arguments: column_ids must be a non-empty array of positive integers');
            }

            $columnIds = [];

            foreach ($rawIds as $rawId) {
                $id = self::toId($rawId);

                if ($id === null) {
                    throw new ScopeViolation('Invalid arguments: column_ids must contain positive integers');
                }

                $columnIds[] = $id;
                $projects[] = $this->resolveProject('column_id', $id);
            }

            $arguments['column_ids'] = $columnIds;
        }

        if ($projects === [] && !in_array($tool, self::PROJECTLESS_TOOLS, true)) {
            throw new ScopeViolation(self::OUT_OF_SCOPE);
        }

        foreach ($projects as $projectId) {
            if (!$this->scope->allowsProject($projectId)) {
                throw new ScopeViolation(self::OUT_OF_SCOPE);
            }
        }

        return $arguments;
    }

    /**
     * Narrow the result of a listing tool to the allowed projects.
     */
    public function filterResult(string $tool, mixed $result): mixed
    {
        if (!$this->scope->isRestricted() || !is_array($result)) {
            return $result;
        }

        return match ($tool) {
            'get_projects' => $this->filterProjects($result),
            'get_users' => $this->filterUsers($result),
            'get_tags' => $this->filterByProjectColumn($result),
            default => $result,
        };
    }

    /**
     * @param array<int,array<string,mixed>> $projects
     * @return array<int,array<string,mixed>>
     */
    public function filterProjects(array $projects): array
    {
        if (!$this->scope->isRestricted()) {
            return $projects;
        }

        return array_values(array_filter(
            $projects,
            fn($project): bool => is_array($project) && $this->scope->allowsProject((int) ($project['id'] ?? 0))
        ));
    }

    /**
     * Keep only users who are members of an allowed project.
     *
     * @param array<int,array<string,mixed>> $users
     * @return array<int,array<string,mixed>>
     */
    public function filterUsers(array $users): array
    {
        if (!$this->scope->isRestricted()) {
            return $users;
        }

        $memberIds = [];

        foreach ($this->scope->getAllowedProjects() ?? [] as $projectId) {
            foreach ($this->container['projectPermissionModel']->getMembers($projectId) as $member) {
                $memberIds[(int) ($member['id'] ?? 0)] = true;
            }
        }

        return array_values(array_filter(
            $users,
            static fn($user): bool => is_array($user) && isset($memberIds[(int) ($user['id'] ?? 0)])
        ));
    }

    /**
     * Global tags (project_id 0) belong to no project, so they are dropped too.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function filterByProjectColumn(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn($row): bool => is_array($row) && $this->scope->allowsProject((int) ($row['project_id'] ?? 0))
        ));
    }

    private function resolveProject(string $key, int $id): int
    {
        $c = $this->container;

        return match ($key) {
            'project_id' => $id,
            'task_id' => (int) $c['taskFinderModel']->getProjectId($id),
            'column_id' => (int) $c['columnModel']->getProjectId($id),
            'swimlane_id' => (int) ($c['swimlaneModel']->getById($id)['project_id'] ?? 0),
            'category_id' => (int) $c['categoryModel']->getProjectId($id),
            'tag_id' => (int) ($c['tagModel']->getById($id)['project_id'] ?? 0),
            'comment_id' => (int) $c['commentModel']->getProjectId($id),
            default => 0,
        };
    }

    /**
     * Strict id parsing: only forms every handler reads the same way.
     */
    private static function toId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit($value) && strlen($value) <= 18) {
            return (int) $value;
        }

        if (is_float($value) && $value >= 0 && $value < 1e15 && floor($value) === $value) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Same shapes the reorder_columns handler accepts; anything else is not a list.
     *
     * @return array<int,mixed>|null
     */
    private function toList(mixed $value): ?array
    {
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), static fn(string $v): bool => $v !== ''));
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value, false);
        }

        return is_array($value) ? array_values($value) : null;
    }
}
