<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 * @template TKey
 * @template-covariant TValue
 * @extends Collection<TKey, TValue>
 */
interface OrderedMap extends Collection
{
    /**
     * @template TNewKey
     * @template TNewValue
     * @param TNewKey $key
     * @param TNewValue $value
     * @return static<TKey|TNewKey, TValue|TNewValue>
     */
    public function with(mixed $key, mixed $value): static;

    /**
     * @param TKey ...$keys
     */
    public function without(mixed ...$keys): static;

    /**
     * @return (TKey is array-key ? array<TKey, TValue> : never)
     */
    public function toArray(): array;

    /**
     * @return self<TKey, TValue>
     */
    public function toMutable(): self;
}
