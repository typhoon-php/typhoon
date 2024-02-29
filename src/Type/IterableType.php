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
     * @param Type<TKey> $key
     * @param Type<TValue> $value
     */
    public function __construct(
        private readonly Type $key,
        private readonly Type $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->iterable($this, $this->key, $this->value);
    }
}
