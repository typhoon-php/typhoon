<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TArray of array
 * @implements Type<TArray>
 */
final class ShapeType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param array<ShapeElement> $elements
     */
    public function __construct(
        public readonly array $elements = [],
        public readonly bool $sealed = true,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitShape($this);
    }
}
