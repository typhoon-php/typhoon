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
     * @param Type<TKey> $key
     * @param Type<TValue> $value
     */
    public function __construct(
        private readonly Type $key,
        private readonly Type $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->array($this, $this->key, $this->value);
    }
}
