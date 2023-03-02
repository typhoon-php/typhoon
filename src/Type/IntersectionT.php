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
final class IntersectionT implements Type
{
    /**
     * @var non-empty-list<Type>
     */
    public readonly array $types;

    /**
     * @no-named-arguments
     */
    public function __construct(
        Type $type1,
        Type $type2,
        Type ...$moreTypes,
    ) {
        $this->types = [$type1, $type2, ...$moreTypes];
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntersection($this);
    }
}
