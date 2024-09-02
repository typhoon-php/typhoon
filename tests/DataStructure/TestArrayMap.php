<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\Encoder;

/**
 * @template K
 * @template V
 * @extends MutableMap<K, V>
 */
final class TestArrayMap extends MutableMap
{
    /**
     * @param array<KVPair<K, V>> $kvPairs
     */
    public function __construct(
        private array $kvPairs = [],
    ) {}

    public function contains(mixed $key): bool
    {
        return isset($this->kvPairs[Encoder::encode($key)]);
    }

    public function getOr(mixed $key, callable $or): mixed
    {
        $encodedKey = Encoder::encode($key);

        if (isset($this->kvPairs[$encodedKey])) {
            return $this->kvPairs[$encodedKey]->value;
        }

        return $or();
    }

    public function usortKV(callable $comparator): static
    {
        $kvPairs = $this->kvPairs;
        uasort(
            $kvPairs,
            /**
             * @param KVPair<K, V> $kv1
             * @param KVPair<K, V> $kv2
             */
            static fn(KVPair $kv1, KVPair $kv2) => $comparator($kv1->key, $kv1->value, $kv2->key, $kv2->value),
        );

        return new self($kvPairs);
    }

    public function put(mixed $key, mixed $value): void
    {
        $this->kvPairs[Encoder::encode($key)] = new KVPair($key, $value);
    }

    public function remove(mixed ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->kvPairs[Encoder::encode($key)]);
        }
    }

    public function clear(): void
    {
        $this->kvPairs = [];
    }

    /**
     * @return \Generator<K, V>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->kvPairs as $kvPair) {
            yield $kvPair->key => $kvPair->value;
        }
    }
}
