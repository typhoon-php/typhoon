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
     * @param KVPair<NK, NV> ...$kvPairs
     * @return self<NK, NV>
     */
    public static function fromPairs(KVPair ...$kvPairs): self
    {
        return ArrayMap::fromPairs(...$kvPairs);
    }

    /**
     * @template NK
     * @template NV
     * @param KVPair<NK, NV> ...$kvPairs
     * @return static<K|NK, V|NV>
     */
    final public function withPairs(KVPair ...$kvPairs): static
    {
        if ($kvPairs === []) {
            return $this;
        }

        $map = clone $this;
        /** @psalm-suppress InvalidArgument */
        $map->putPairs(...$kvPairs);

        return $map;
    }

    /**
     * @template NK
     * @template NV
     * @param iterable<NK, NV>|\Closure(): iterable<NK, NV> $values
     * @return static<K|NK, V|NV>
     */
    final public function withAll(iterable|\Closure $values): static
    {
        if ($values instanceof \Closure) {
            $values = $values();
        }

        if ($values === []) {
            return $this;
        }

        $map = clone $this;
        /** @psalm-suppress InvalidArgument */
        $map->putAll($values);

        return $map;
    }

    /**
     * @return static<K, V>
     */
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
    abstract public function put(mixed $key, mixed $value): void;

    /**
     * @param KVPair<K, V> ...$kvPairs
     */
    abstract public function putPairs(KVPair ...$kvPairs): void;

    /**
     * @param iterable<K, V>|\Closure(): iterable<K, V> $values
     */
    abstract public function putAll(iterable|\Closure $values): void;

    abstract public function remove(mixed ...$keys): void;

    abstract public function clear(): void;
}
