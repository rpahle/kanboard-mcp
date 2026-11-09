<?php

namespace Kanboard\Plugin\ModelContextProtocol\Model;

use Kanboard\Core\Base;

/**
 * MCP Token Model
 *
 * @package  Kanboard\Plugin\ModelContextProtocol\Model
 * @author   Plugin Author
 */
class McpTokenModel extends Base
{
    const TABLE = 'mcp_tokens';

    /**
     * Validate token
     */
    public function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        return $this->db->table(self::TABLE)
            ->eq('token', $token)
            ->eq('is_active', 1)
            ->exists();
    }

    /**
     * Get the active token row, including its scope columns
     *
     * @return array|null
     */
    public function getByToken($token)
    {
        if (empty($token) || !is_string($token)) {
            return null;
        }

        $row = $this->db->table(self::TABLE)
            ->eq('token', $token)
            ->eq('is_active', 1)
            ->findOne();

        return $row ?: null;
    }

    /**
     * List tokens for the admin page. The token value is never selected.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->db->table(self::TABLE)
            ->columns('id', 'name', 'allowed_projects', 'blocked_tools', 'is_active', 'created_at')
            ->asc('id')
            ->findAll();
    }

    /**
     * One token for the admin pages, without its value
     *
     * @return array|null
     */
    public function getSummary($id)
    {
        $row = $this->db->table(self::TABLE)
            ->columns('id', 'name', 'allowed_projects', 'blocked_tools', 'is_active', 'created_at')
            ->eq('id', (int) $id)
            ->findOne();

        return $row ?: null;
    }

    /**
     * Delete a token. It stops authenticating immediately.
     *
     * @return bool
     */
    public function remove($id)
    {
        return $this->db->table(self::TABLE)->eq('id', (int) $id)->remove();
    }

    /**
     * Create a token. A NULL scope column means unrestricted.
     *
     * @param  string      $name
     * @param  string|null $allowedProjects Comma-separated project ids
     * @param  string|null $blockedTools    Comma-separated tool names or prefixes
     * @return string|false The new token value, returned only here so it can be shown once
     */
    public function create($name, $allowedProjects, $blockedTools)
    {
        $token = bin2hex(random_bytes(32));

        $id = $this->db->table(self::TABLE)->persist([
            'token' => $token,
            'name' => $name,
            'allowed_projects' => $allowedProjects,
            'blocked_tools' => $blockedTools,
            'is_active' => 1,
            'created_at' => time(),
        ]);

        return $id ? $token : false;
    }

    /**
     * Check if token exists
     */
    public function tokenExists()
    {
        return $this->db->table(self::TABLE)
            ->eq('is_active', 1)
            ->exists();
    }
}
