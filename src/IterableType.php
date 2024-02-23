<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TKey
 * @template-covariant TValue
 * @implements Type<iterable<TKey, TValue>>
 */
final class IterableType implements Type
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
        return $visitor->iterable($this, $this->keyType, $this->valueType);
    }
}
