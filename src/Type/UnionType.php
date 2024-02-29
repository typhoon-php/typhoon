<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TType
 * @implements Type<TType>
 */
final class UnionType implements Type
{
    /**
     * @param non-empty-list<Type<TType>> $types
     */
    public function __construct(
        private readonly array $types,
    ) {
        \assert(\count($types) >= 2, 'Union type must contain at least 2 types.');
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->union($this, $this->types);
    }
}
