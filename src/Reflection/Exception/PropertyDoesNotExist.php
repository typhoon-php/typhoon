<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class PropertyDoesNotExist extends ReflectionException
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class, string $name, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Property %s::$%s does not exist', self::normalizeClass($class), $name), previous: $previous);
    }
}
