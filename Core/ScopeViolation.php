<?php
declare(strict_types=1);

namespace Kanboard\Plugin\ModelContextProtocol\Core;

use RuntimeException;

/**
 * A tool call that the token is not allowed to make.
 * The message is safe to return to the caller.
 */
final class ScopeViolation extends RuntimeException
{
}
