<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T
 * @implements Type<T>
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
