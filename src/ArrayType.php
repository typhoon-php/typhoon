<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TKey
 * @template-covariant TValue
 * @implements Type<array<TKey, TValue>>
 */
final class ArrayType implements Type
{
    /**
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     */
    public function __construct(
        private readonly Type $keyType,
        private readonly Type $valueType,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->array($this, $this->keyType, $this->valueType);
    }
}
