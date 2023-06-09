<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TKey
 * @template-covariant TValue
 * @implements Type<iterable<TKey, TValue>>
 */
final class IterableType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TKey>   $keyType
     * @param Type<TValue> $valueType
     */
    public function __construct(
        public readonly Type $keyType = MixedType::type,
        public readonly Type $valueType = MixedType::type,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIterable($this);
    }
}
