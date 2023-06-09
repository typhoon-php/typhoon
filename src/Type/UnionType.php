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
final class UnionType implements Type
{
    /**
     * @var non-empty-list<Type<TType>>
     */
    public readonly array $types;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @no-named-arguments
     * @param non-empty-list<Type<TType>> $types
     */
    public function __construct(
        array $types,
    ) {
        \assert(\count($types) >= 2, 'Union type must contain at least 2 types.');

        $this->types = $types;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitUnion($this);
    }
}
