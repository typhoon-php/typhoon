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
     * @param callable-string $function
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $function,
        public readonly string $name,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFunctionTemplate($this);
    }
}
