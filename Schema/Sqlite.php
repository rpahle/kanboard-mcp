<?php

namespace Kanboard\Plugin\ModelContextProtocol\Schema;

const VERSION = 2;

function version_1($pdo)
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS mcp_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token TEXT NOT NULL UNIQUE,
            is_active INTEGER DEFAULT 1,
            created_at INTEGER NOT NULL
        )
    ');
    
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mcp_token ON mcp_tokens (token)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_mcp_active ON mcp_tokens (is_active)');
}

/**
 * Per-token scopes. NULL means unrestricted, so existing tokens keep working.
 * Safe to run more than once: each column is only added when it is missing.
 */
function version_2($pdo)
{
    $existing = array_column($pdo->query('PRAGMA table_info(mcp_tokens)')->fetchAll(\PDO::FETCH_ASSOC), 'name');

    foreach (['name', 'allowed_projects', 'blocked_tools'] as $column) {
        if (!in_array($column, $existing, true)) {
            $pdo->exec('ALTER TABLE mcp_tokens ADD COLUMN ' . $column . ' TEXT NULL');
        }
    }
}
