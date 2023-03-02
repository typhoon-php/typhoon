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
final class ShapeT implements Type
{
    /**
     * @var array<ShapeElement>
     */
    public readonly array $elements;

    /**
     * @param array<Type|ShapeElement> $elements
     */
    public function __construct(
        array $elements = [],
        public readonly bool $sealed = true,
    ) {
        $this->elements = array_map(
            static fn (Type|ShapeElement $element): ShapeElement => $element instanceof Type ? new ShapeElement($element) : $element,
            $elements,
        );
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitShape($this);
    }
}
