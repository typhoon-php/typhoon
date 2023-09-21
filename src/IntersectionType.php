<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class IntersectionType implements Type
{
    /**
     * @var non-empty-list<Type>
     */
    public readonly array $types;

    /**
     * @internal
     * @psalm-internal Typhoon
     * @no-named-arguments
     * @param non-empty-list<Type> $types
     */
    public function __construct(
        array $types,
    ) {
        \assert(\count($types) >= 2, 'Intersection type must contain at least 2 types.');

        $this->types = $types;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntersection($this);
    }
}
