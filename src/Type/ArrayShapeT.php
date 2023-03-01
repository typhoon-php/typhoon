<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T of array
 * @implements Type<T>
 */
final class ArrayShapeT implements Type
{
    /**
     * @var array<ArrayShapeItem>
     */
    public readonly array $items;

    /**
     * @param array<Type|ArrayShapeItem> $items
     */
    public function __construct(
        array $items = [],
        public readonly bool $sealed = true,
    ) {
        $this->items = array_map(
            static fn (Type|ArrayShapeItem $item): ArrayShapeItem => $item instanceof Type ? new ArrayShapeItem($item) : $item,
            $items,
        );
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitArrayShape($this);
    }
}
