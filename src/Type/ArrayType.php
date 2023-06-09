<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TKey of array-key
 * @template-covariant TValue
 * @implements Type<array<TKey, TValue>>
 */
final class ArrayType implements Type
{
    /**
     * @var Type<TKey>
     */
    public readonly Type $keyType;

    /**
     * @var Type<TValue>
     */
    public readonly Type $valueType;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     */
    public function __construct(
        Type $keyType = ArrayKeyType::type,
        Type $valueType = MixedType::type,
    ) {
        $this->valueType = $valueType;
        $this->keyType = $keyType;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitArray($this);
    }
}
