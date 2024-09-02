<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use Typhoon\DataStructure\KVPair;
use Typhoon\DataStructure\MutableMap;

/**
 * @internal
 * @psalm-internal Typhoon\DataStructure
 * @template K
 * @template V
 * @extends MutableMap<K, V>
 */
final class ArrayMap extends MutableMap
{
    /**
     * @param array<KVPair<K, V>> $kvPairs
     */
    public function __construct(
        private array $kvPairs = [],
    ) {}

    public function with(mixed $key, mixed $value): static
    {
        $map = clone $this;
        $map->kvPairs[Encoder::encode($key)] = new KVPair($key, $value);

        return $map;
    }

    public function put(mixed $key, mixed $value): void
    {
        $this->kvPairs[Encoder::encode($key)] = new KVPair($key, $value);
    }

    public function putPairs(KVPair ...$kvPairs): void
    {
        foreach ($kvPairs as $kvPair) {
            $this->kvPairs[Encoder::encode($kvPair->key)] = $kvPair;
        }
    }

    protected function doPutAll(iterable $values): void
    {
        if ($values instanceof self) {
            $this->kvPairs = array_replace($this->kvPairs, $values->kvPairs);

            return;
        }

        foreach ($values as $key => $value) {
            $this->kvPairs[Encoder::encode($key)] = new KVPair($key, $value);
        }
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

    public function isEmpty(): bool
    {
        return $this->kvPairs === [];
    }

    public function count(): int
    {
        return \count($this->kvPairs);
    }

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

    public function first(): ?KVPair
    {
        $key = array_key_first($this->kvPairs);

        if ($key === null) {
            return null;
        }

        return $this->kvPairs[$key];
    }

    public function last(): ?KVPair
    {
        $key = array_key_last($this->kvPairs);

        if ($key === null) {
            return null;
        }

        return $this->kvPairs[$key];
    }

    /**
     * @template R
     * @param callable(V|R, K, V): R $operation
     * @return V|R
     */
    public function reduceKV(callable $operation): mixed
    {
        $kvPairs = $this->kvPairs;
        $initial = array_shift($kvPairs) ?? throw new \RuntimeException('Empty map');

        if ($kvPairs === []) {
            return $initial->value;
        }

        return array_reduce(
            $kvPairs,
            /**
             * @param V|R $accumulator
             * @param KVPair<K,V> $kv
             */
            static fn(mixed $accumulator, KVPair $kv): mixed => $operation($accumulator, $kv->key, $kv->value),
            $initial->value,
        );
    }

    /**
     * @template I
     * @template R
     * @param I $initial
     * @param callable(I|R, V): R $operation
     * @return I|R
     */
    public function fold(mixed $initial, callable $operation): mixed
    {
        return array_reduce(
            $this->kvPairs,
            /**
             * @param I|R $accumulator
             * @param KVPair<K,V> $kv
             */
            static fn(mixed $accumulator, KVPair $kv): mixed => $operation($accumulator, $kv->value),
            $initial,
        );
    }

    /**
     * @template I
     * @template R
     * @param I $initial
     * @param callable(I|R, K, V): R $operation
     * @return I|R
     */
    public function foldKV(mixed $initial, callable $operation): mixed
    {
        return array_reduce(
            $this->kvPairs,
            /**
             * @param I|R $accumulator
             * @param KVPair<K,V> $kv
             */
            static fn(mixed $accumulator, KVPair $kv): mixed => $operation($accumulator, $kv->key, $kv->value),
            $initial,
        );
    }

    public function filter(callable $predicate): static
    {
        return new self(array_filter($this->kvPairs, static fn(KVPair $kv): bool => $predicate($kv->value)));
    }

    public function filterKV(callable $predicate): static
    {
        return new self(array_filter($this->kvPairs, static fn(KVPair $kv): bool => $predicate($kv->key, $kv->value)));
    }

    public function map(callable $mapper): static
    {
        return new self(array_map(
            static fn(KVPair $kv): KVPair => $kv->withValue($mapper($kv->value)),
            $this->kvPairs,
        ));
    }

    public function mapKV(callable $mapper): static
    {
        return new self(array_map(
            static fn(KVPair $kv): KVPair => $kv->withValue($mapper($kv->key, $kv->value)),
            $this->kvPairs,
        ));
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

    public function slice(int $offset, ?int $length = null): static
    {
        return new self(\array_slice($this->kvPairs, $offset, $length, preserve_keys: true));
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
