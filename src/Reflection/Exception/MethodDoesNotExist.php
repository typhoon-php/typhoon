<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class MethodDoesNotExist extends ReflectionException
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class, string $name, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Method %s::%s() does not exist', self::normalizeClass($class), $name), previous: $previous);
    }
}
