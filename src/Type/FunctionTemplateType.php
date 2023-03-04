<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class FunctionTemplateType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param non-empty-string $name
     * @param callable-string $function
     */
    public function __construct(
        public readonly string $name,
        public readonly string $function,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFunctionTemplate($this);
    }
}
