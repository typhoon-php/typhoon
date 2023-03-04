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
 * @implements Type<array<TKey, TValue>>
 */
final class ArrayType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     */
    public function __construct(
        public readonly Type $keyType = ArrayKeyType::self,
        public readonly Type $valueType = MixedType::self,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitArray($this);
    }
}
