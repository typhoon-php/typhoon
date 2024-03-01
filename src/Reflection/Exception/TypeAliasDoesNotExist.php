<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class TypeAliasDoesNotExist extends ReflectionException
{
    public function __construct(string $name, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Type alias "%s" does not exist', $name), previous: $previous);
    }
}
