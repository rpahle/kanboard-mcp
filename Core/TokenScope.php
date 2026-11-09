<?php
declare(strict_types=1);

namespace Kanboard\Plugin\ModelContextProtocol\Core;

use InvalidArgumentException;

/**
 * What a single MCP token is allowed to reach.
 *
 * allowed_projects: NULL means unrestricted. Any other value, including an
 * empty string, restricts the token to the listed projects, so a damaged
 * value narrows access instead of widening it.
 *
 * blocked_tools: comma-separated tool names. Each entry is matched as a
 * prefix, so "delete_" blocks every delete_* tool and "create_project"
 * blocks that one tool.
 */
final class TokenScope
{
    /**
     * @param array<int,int>|null $allowedProjects
     * @param array<int,string>   $blockedTools
     */
    private function __construct(
        private readonly ?array $allowedProjects,
        private readonly array $blockedTools
    ) {
    }

    public static function unrestricted(): self
    {
        return new self(null, []);
    }

    /**
     * @param array<string,mixed> $row A mcp_tokens row; missing columns mean "no restriction"
     */
    public static function fromRow(array $row): self
    {
        $projects = $row['allowed_projects'] ?? null;
        $tools = $row['blocked_tools'] ?? null;

        return new self(
            $projects === null ? null : self::parseProjects((string) $projects, false),
            $tools === null ? [] : self::parseTools((string) $tools)
        );
    }

    public function isRestricted(): bool
    {
        return $this->allowedProjects !== null;
    }

    /**
     * @return array<int,int>|null
     */
    public function getAllowedProjects(): ?array
    {
        return $this->allowedProjects;
    }

    /**
     * @return array<int,string>
     */
    public function getBlockedTools(): array
    {
        return $this->blockedTools;
    }

    public function allowsProject(int $projectId): bool
    {
        return $this->allowedProjects === null
            || ($projectId > 0 && in_array($projectId, $this->allowedProjects, true));
    }

    public function isToolBlocked(string $tool): bool
    {
        $tool = strtolower(trim($tool));

        foreach ($this->blockedTools as $blocked) {
            if (str_starts_with($tool, $blocked)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a comma-separated list of project ids.
     *
     * @param bool $strict Reject malformed entries (form input) instead of dropping them (stored values)
     * @return array<int,int>
     * @throws InvalidArgumentException in strict mode
     */
    public static function parseProjects(string $input, bool $strict): array
    {
        $projects = [];

        foreach (explode(',', $input) as $entry) {
            $entry = trim($entry);

            if ($entry === '') {
                continue;
            }

            if (!ctype_digit($entry) || strlen($entry) > 18 || (int) $entry <= 0) {
                if ($strict) {
                    throw new InvalidArgumentException('Allowed projects must be positive project ids separated by commas');
                }

                continue;
            }

            $projects[] = (int) $entry;
        }

        return array_values(array_unique($projects));
    }

    /**
     * @return array<int,string>
     */
    public static function parseTools(string $input): array
    {
        $tools = [];

        foreach (explode(',', $input) as $entry) {
            $entry = strtolower(trim($entry));

            if ($entry !== '') {
                $tools[] = $entry;
            }
        }

        return array_values(array_unique($tools));
    }
}
