<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class MethodDoesNotHavePrototype extends ReflectionException
{
    /**
     * @param class-string $class
     * @param non-empty-string $name
     */
    public function __construct(string $class, string $name, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Method %s::%s does not have a prototype', ReflectionException::normalizeClass($class), $name),
            previous: $previous,
        );
    }
}
