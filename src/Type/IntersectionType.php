<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class IntersectionType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @no-named-arguments
     * @param non-empty-list<Type> $types
     */
    public function __construct(
        public readonly array $types,
    ) {
        \assert(\count($types) >= 2, 'Intersection type must contain at least 2 types.');
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntersection($this);
    }
}
