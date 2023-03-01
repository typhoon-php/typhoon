<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T
 * @implements Type<T>
 */
final class UnionT implements Type
{
    /**
     * @var non-empty-list<Type<T>>
     */
    public readonly array $types;

    /**
     * @no-named-arguments
     * @param Type<T> $type1
     * @param Type<T> $type2
     * @param Type<T> ...$moreTypes
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
        return $visitor->visitUnion($this);
    }
}
