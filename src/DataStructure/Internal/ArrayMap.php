<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use Typhoon\DataStructure\KVPair;
use Typhoon\DataStructure\MutableMap;
use Typhoon\DataStructure\Sequence;

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
     * @template NK
     * @template NV
     * @param iterable<NK, NV>|\Closure(): iterable<NK, NV> $values
     * @return self<NK, NV>
     */
    public static function of(iterable|\Closure $values = []): self
    {
        /** @var self<NK, NV> */
        $map = new self();
        $map->putAll($values);

        return $map;
    }

    /**
     * @template NK
     * @template NV
     * @param KVPair<NK, NV> ...$kvPairs
     * @return self<NK, NV>
     */
    public static function fromPairs(KVPair ...$kvPairs): self
    {
        /** @var self<NK, NV> */
        $map = new self();
        $map->putPairs(...$kvPairs);

        return $map;
    }

    /**
     * @template NK
     * @template NV
     * @param iterable<NK> $keys
     * @param callable(NK): NV $value
     * @return self<NK, NV>
     */
    public static function fromKeys(iterable $keys, callable $value): self
    {
        $map = new self();

        foreach ($keys as $key) {
            $map->put($key, $value($key));
        }

        return $map;
    }

    /**
     * @template NK
     * @template NV
     * @param iterable<NV> $values
     * @param callable(NV): NK $key
     * @return self<NK, NV>
     */
    public static function fromValues(iterable $values, callable $key): self
    {
        $map = new self();

        foreach ($values as $value) {
            $map->put($key($value), $value);
        }

        return $map;
    }

    /**
     * @param array<KVPair<K, V>> $kvPairs
     */
    private function __construct(
        private array $kvPairs = [],
    ) {}

    public function with(mixed $key, mixed $value): static
    {
        $map = clone $this;
        $map->kvPairs[ArrayMapKeyEncoder::encode($key)] = new KVPair($key, $value);

        return $map;
    }

    public function put(mixed $key, mixed $value): void
    {
        $this->kvPairs[ArrayMapKeyEncoder::encode($key)] = new KVPair($key, $value);
    }

    public function putPairs(KVPair ...$kvPairs): void
    {
        foreach ($kvPairs as $kvPair) {
            $this->kvPairs[ArrayMapKeyEncoder::encode($kvPair->key)] = $kvPair;
        }
    }

    public function putAll(iterable|\Closure $values): void
    {
        if ($values instanceof \Closure) {
            $values = $values();
        }

        if ($values instanceof self) {
            $this->kvPairs = [...$this->kvPairs, ...$values->kvPairs];

            return;
        }

        foreach ($values as $key => $value) {
            $this->kvPairs[ArrayMapKeyEncoder::encode($key)] = new KVPair($key, $value);
        }
    }

    public function remove(mixed ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->kvPairs[ArrayMapKeyEncoder::encode($key)]);
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
        return isset($this->kvPairs[ArrayMapKeyEncoder::encode($key)]);
    }

    public function getOr(mixed $key, callable $or): mixed
    {
        $encodedKey = ArrayMapKeyEncoder::encode($key);

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
            static fn (mixed $accumulator, KVPair $kv): mixed => $operation($accumulator, $kv->key, $kv->value),
            $initial->value,
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
            static fn (mixed $accumulator, KVPair $kv): mixed => $operation($accumulator, $kv->key, $kv->value),
            $initial,
        );
    }

    public function filterKV(callable $predicate): static
    {
        return new self(array_filter($this->kvPairs, static fn (KVPair $kv): bool => $predicate($kv->key, $kv->value)));
    }

    public function mapKV(callable $mapper): static
    {
        return new self(array_map(static fn (KVPair $kv): KVPair => $kv->withValue($mapper($kv->key, $kv->value)), $this->kvPairs));
    }

    public function flip(): static
    {
        $map = new self();

        foreach ($this->kvPairs as $kvPair) {
            $map->kvPairs[ArrayMapKeyEncoder::encode($kvPair->value)] = $kvPair->flip();
        }

        return $map;
    }

    public function reverse(): static
    {
        return new self(array_reverse($this->kvPairs, preserve_keys: true));
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
            static fn (KVPair $kv1, KVPair $kv2) => $comparator($kv1->key, $kv1->value, $kv2->key, $kv2->value)
        );

        return new self($kvPairs);
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new self(\array_slice($this->kvPairs, $offset, $length));
    }

    public function keys(): Sequence
    {
        throw new \LogicException('TODO');
    }

    public function values(): Sequence
    {
        throw new \LogicException('TODO');
    }

    public function pairs(): Sequence
    {
        throw new \LogicException('TODO');
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
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
