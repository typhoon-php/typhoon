<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class InterfaceDoesNotExist extends ReflectionException
{
    public function __construct(string $class, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Interface "%s" does not exist', self::normalizeClass($class)), previous: $previous);
    }
}
