<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 * @template TKey
 * @template TValue
 * @extends OrderedMap<TKey, TValue>
 */
interface MutableOrderedMap extends OrderedMap
{
    /**
     * @param TKey $key
     * @param TValue $value
     */
    public function set(mixed $key, mixed $value): void;

    /**
     * @param TKey ...$keys
     */
    public function unset(mixed ...$keys): void;
}
