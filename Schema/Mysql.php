<?php

namespace Kanboard\Plugin\ModelContextProtocol\Schema;

const VERSION = 2;

function version_1($pdo)
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mcp_tokens (
            id INT NOT NULL AUTO_INCREMENT,
            token VARCHAR(255) NOT NULL UNIQUE,
            is_active TINYINT(1) DEFAULT 1,
            created_at INT NOT NULL,
            PRIMARY KEY(id),
            KEY idx_token (token),
            KEY idx_active (is_active)
        ) ENGINE=InnoDB CHARSET=utf8
    ');
}

/**
 * Per-token scopes. NULL means unrestricted, so existing tokens keep working.
 * Safe to run more than once: each column is only added when it is missing.
 */
function version_2($pdo)
{
    $stmt = $pdo->query(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mcp_tokens'"
    );
    $existing = array_map('strtolower', $stmt->fetchAll(\PDO::FETCH_COLUMN));

    $columns = [
        'name' => 'VARCHAR(255) NULL',
        'allowed_projects' => 'TEXT NULL',
        'blocked_tools' => 'TEXT NULL',
    ];

    foreach ($columns as $column => $definition) {
        if (!in_array($column, $existing, true)) {
            $pdo->exec('ALTER TABLE mcp_tokens ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
}
