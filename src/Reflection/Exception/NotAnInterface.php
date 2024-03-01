<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class NotAnInterface extends ReflectionException
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('%s is not an interface', self::normalizeClass($class)), previous: $previous);
    }
}
