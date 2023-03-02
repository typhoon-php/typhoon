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
final class ArrayShapeT implements Type
{
    /**
     * @var array<ArrayElement>
     */
    public readonly array $elements;

    /**
     * @param array<Type|ArrayElement> $elements
     */
    public function __construct(
        array $elements = [],
        public readonly bool $sealed = true,
    ) {
        $this->elements = array_map(
            static fn (Type|ArrayElement $element): ArrayElement => $element instanceof Type ? new ArrayElement($element) : $element,
            $elements,
        );
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitArrayShape($this);
    }
}
