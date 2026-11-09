<?php
declare(strict_types=1);

namespace Kanboard\Plugin\ModelContextProtocol\Core;

use Throwable;

/**
 * Keeps credentials out of everything the MCP server hands back to a caller.
 *
 * Two layers:
 *  1. User records are reduced to an allowlist of fields.
 *  2. Every other key that looks like a credential is removed, recursively.
 */
final class ResponseSanitizer
{
    /**
     * The only fields a user record may expose.
     *
     * @var array<int,string>
     */
    private const USER_FIELDS = ['id', 'username', 'name', 'role', 'is_active'];

    /**
     * Columns that only exist on user rows. A record with a username plus any
     * of these is treated as a user even when it is nested inside another result.
     *
     * @var array<int,string>
     */
    private const USER_MARKERS = [
        'role',
        'password',
        'api_access_token',
        'totp_secret',
        'is_ldap_user',
        'nb_failed_login',
        'twofactor_activated',
        'google_id',
        'github_id',
        'gitlab_id',
    ];

    private const SECRET_KEY_PATTERN = '/token|password|secret|hash|api_access|oauth|_id$/i';

    /**
     * Identifier-style keys that match the pattern above but are not credentials.
     *
     * @var array<string,true>
     */
    private const SAFE_KEYS = [
        'id' => true,
        'user_id' => true,
        'project_id' => true,
        'task_id' => true,
        'column_id' => true,
        'owner_id' => true,
        'creator_id' => true,
        'category_id' => true,
        'swimlane_id' => true,
        // Returned by create_tag / add_task_comment and carried by tasks, tags and categories
        'tag_id' => true,
        'comment_id' => true,
        'color_id' => true,
    ];

    private const MAX_DEPTH = 32;

    /**
     * Sanitize any tool or resource payload.
     */
    public static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if (is_object($value)) {
            $decoded = json_decode((string) json_encode($value), true);
            $value = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($value)) {
            return $value;
        }

        if ($depth >= self::MAX_DEPTH) {
            return null;
        }

        if (self::isUserRecord($value)) {
            return self::user($value);
        }

        $clean = [];

        foreach ($value as $key => $child) {
            if (is_string($key) && self::isSecretKey($key)) {
                continue;
            }

            $clean[$key] = self::sanitize($child, $depth + 1);
        }

        return $clean;
    }

    /**
     * Reduce a user record to the allowlisted fields.
     *
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public static function user(array $user): array
    {
        return array_intersect_key($user, array_flip(self::USER_FIELDS));
    }

    public static function isSecretKey(string $key): bool
    {
        return !isset(self::SAFE_KEYS[strtolower($key)])
            && preg_match(self::SECRET_KEY_PATTERN, $key) === 1;
    }

    /**
     * Describe a log context by shape only: key names and value types, never values.
     *
     * @param array<mixed> $context
     * @return array<mixed>
     */
    public static function describeForLog(array $context, int $depth = 0): array
    {
        $described = [];

        foreach ($context as $key => $value) {
            if ($value instanceof Throwable) {
                $described[$key] = self::describeThrowable($value);
            } elseif (is_array($value)) {
                $described[$key] = $depth >= self::MAX_DEPTH ? 'array' : self::describeForLog($value, $depth + 1);
            } else {
                $described[$key] = get_debug_type($value);
            }
        }

        return $described;
    }

    /**
     * Where an exception came from, without its message: messages from the
     * database layer can contain the offending values.
     *
     * @return array<string,mixed>
     */
    public static function describeThrowable(Throwable $throwable): array
    {
        return [
            'class' => get_class($throwable),
            'code' => $throwable->getCode(),
            'location' => basename($throwable->getFile()) . ':' . $throwable->getLine(),
        ];
    }

    /**
     * @param array<mixed> $record
     */
    private static function isUserRecord(array $record): bool
    {
        if (!array_key_exists('username', $record)) {
            return false;
        }

        foreach (self::USER_MARKERS as $marker) {
            if (array_key_exists($marker, $record)) {
                return true;
            }
        }

        return false;
    }
}
