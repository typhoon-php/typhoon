<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TArray of array
 * @implements Type<TArray>
 */
final class ShapeType implements Type
{
    /**
     * @var array<ShapeElement>
     */
    public readonly array $elements;
    public readonly bool $sealed;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param array<ShapeElement> $elements
     */
    public function __construct(
        array $elements = [],
        bool $sealed = true,
    ) {
        $this->sealed = $sealed;
        $this->elements = $elements;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitShape($this);
    }
}
