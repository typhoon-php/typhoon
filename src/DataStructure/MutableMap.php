<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\ArrayMap;

/**
 * @api
 * @template K
 * @template V
 * @extends Map<K, V>
 */
abstract class MutableMap extends Map
{
    /**
     * @template NK
     * @template NV
     * @param iterable<NK, NV>|\Closure(): iterable<NK, NV> $values
     * @return self<NK, NV>
     */
    public static function of(iterable|\Closure $values = []): self
    {
        return ArrayMap::of($values);
    }

    /**
     * @template NK
     * @template NV
     * @param KeyValue<NK, NV> ...$keyValues
     * @return self<NK, NV>
     */
    public static function ofKV(KeyValue ...$keyValues): self
    {
        return ArrayMap::ofKV(...$keyValues);
    }

    final public function withKV(KeyValue ...$keyValues): static
    {
        $map = clone $this;
        $map->putKV(...$keyValues);

        return $map;
    }

    public function withAll(iterable|\Closure $values): static
    {
        $map = clone $this;
        $map->putAll($values);

        return $map;
    }

    final public function without(mixed ...$keys): static
    {
        $map = clone $this;
        $map->remove(...$keys);

        return $map;
    }

    /**
     * @param K $key
     * @param V $value
     */
    final public function put(mixed $key, mixed $value): void
    {
        $this->putKV(new KeyValue($key, $value));
    }

    /**
     * @param KeyValue<K, V> ...$keyValues
     */
    abstract public function putKV(KeyValue ...$keyValues): void;

    /**
     * @param iterable<K, V>|\Closure(): iterable<K, V> $values
     */
    abstract public function putAll(iterable|\Closure $values): void;

    abstract public function remove(mixed ...$keys): void;

    abstract public function clear(): void;
}
