<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TKey of array-key
 * @template-covariant TValue
 * @implements Type<non-empty-array<TKey, TValue>>
 */
final class NonEmptyArrayT implements Type
{
    /**
     * @param Type<TKey>   $keyType
     * @param Type<TValue> $valueType
     */
    public function __construct(
        public readonly Type $keyType = new ArrayKeyT(),
        public readonly Type $valueType = new MixedT(),
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNonEmptyArray($this);
    }
}
