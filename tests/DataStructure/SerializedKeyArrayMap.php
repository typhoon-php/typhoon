<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

/**
 * @template K
 * @template V
 * @extends MutableMap<K, V>
 */
final class SerializedKeyArrayMap extends MutableMap
{
    private static function encodeKey(mixed $key): string
    {
        if (\is_resource($key)) {
            return 'r:' . get_resource_id($key) . ';';
        }

        return serialize($key);
    }

    /**
     * @param array<KVPair<K, V>> $kvPairs
     */
    public function __construct(
        private array $kvPairs = [],
    ) {}

    public function contains(mixed $key): bool
    {
        return isset($this->kvPairs[self::encodeKey($key)]);
    }

    public function getOr(mixed $key, callable $or): mixed
    {
        $encodedKey = self::encodeKey($key);

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
        $this->kvPairs[self::encodeKey($key)] = new KVPair($key, $value);
    }

    public function remove(mixed ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->kvPairs[self::encodeKey($key)]);
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
