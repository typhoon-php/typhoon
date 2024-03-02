<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeReflection;

use Typhoon\Reflection\ReflectionException;
use Typhoon\Type\Type;
use function Typhoon\TypeStringifier\stringify;

/**
 * @api
 */
final class NonConvertableType extends ReflectionException
{
    public function __construct(Type $type, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Cannot convert type %s to native ReflectionType', stringify($type)), previous: $previous);
    }
}
