<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\ArrayMap;

/**
 * @api
 * @template K
 * @template V
 * @extends Map<K, V>
 * @psalm-consistent-constructor
 * @psalm-consistent-templates
 */
abstract class MutableMap extends Map
{
    /**
     * @template NK
     * @template NV
     * @param iterable<NK, NV>|\Closure(): iterable<NK, NV> $values
     * @return self<NK, NV>
     */
    final public static function of(iterable|\Closure $values = []): self
    {
        /** @var ArrayMap<NK, NV> */
        $map = new ArrayMap();
        $map->putAll($values);

        return $map;
    }

    /**
     * @no-named-arguments
     * @template NK
     * @template NV
     * @param KVPair<NK, NV> ...$kvPairs
     * @return self<NK, NV>
     */
    final public static function fromPairs(KVPair ...$kvPairs): self
    {
        /** @var ArrayMap<NK, NV> */
        $map = new ArrayMap();
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
    final public static function fromKeys(iterable $keys, callable $value): self
    {
        /** @var ArrayMap<NK, NV> */
        $map = new ArrayMap();

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
    final public static function fromValues(iterable $values, callable $key): self
    {
        /** @var ArrayMap<NK, NV> */
        $map = new ArrayMap();

        foreach ($values as $value) {
            $map->put($key($value), $value);
        }

        return $map;
    }

    /**
     * @template NK
     * @template NV
     * @param NK $key
     * @param NV $value
     * @return static<K|NK, V|NV>
     */
    public function with(mixed $key, mixed $value): static
    {
        $map = clone $this;
        /** @psalm-suppress InvalidArgument */
        $map->put($key, $value);

        return $map;
    }

    /**
     * @no-named-arguments
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
     * @param iterable<NK, NV> $values
     * @return static<K|NK, V|NV>
     */
    protected function doWithAll(iterable $values): static
    {
        $map = clone $this;
        /** @psalm-suppress InvalidArgument */
        $map->putAll($values);

        return $map;
    }

    /**
     * @no-named-arguments
     * @return static<K, V>
     */
    final public function without(mixed ...$keys): static
    {
        if ($keys === []) {
            return $this;
        }

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
     * @no-named-arguments
     * @param KVPair<K, V> ...$kvPairs
     */
    public function putPairs(KVPair ...$kvPairs): void
    {
        foreach ($kvPairs as $kvPair) {
            $this->put($kvPair->key, $kvPair->value);
        }
    }

    /**
     * @param iterable<K, V>|\Closure(): iterable<K, V> $values
     */
    public function putAll(iterable|\Closure $values): void
    {
        if ($values instanceof \Closure) {
            $values = $values();
        }

        if ($values !== []) {
            $this->doPutAll($values);
        }
    }

    /**
     * @param iterable<K, V> $values
     */
    protected function doPutAll(iterable $values): void
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }
    }

    /**
     * @no-named-arguments
     */
    abstract public function remove(mixed ...$keys): void;

    abstract public function clear(): void;

    /**
     * @template NV
     * @param callable(K, V): NV $mapper
     * @return static<K, NV>
     */
    public function mapKV(callable $mapper): static
    {
        $map = new static();

        foreach ($this->getIterator() as $key => $value) {
            /** @psalm-suppress InvalidArgument */
            $map->put($key, $mapper($key, $value));
        }

        return $map;
    }

    /**
     * @param callable(K, V): bool $predicate
     * @return static<K, V>
     */
    public function filterKV(callable $predicate): static
    {
        $map = new static();

        foreach ($this->getIterator() as $key => $value) {
            if ($predicate($key, $value)) {
                $map->put($key, $value);
            }
        }

        return $map;
    }

    /**
     * @template NK
     * @param callable(K, V): NK $mapper
     * @return static<NK, V>
     */
    final public function mapKeyKV(callable $mapper): static
    {
        /** @var static<NK, V> */
        $map = new static();

        foreach ($this->getIterator() as $key => $value) {
            /** @psalm-suppress InvalidArgument */
            $map->put($mapper($key, $value), $value);
        }

        return $map;
    }

    /**
     * @return static<V, K>
     */
    final public function flip(): static
    {
        $map = new static();

        foreach ($this->getIterator() as $key => $value) {
            /** @psalm-suppress InvalidArgument */
            $map->put($value, $key);
        }

        return $map;
    }

    /**
     * @template NK
     * @template NV
     * @param callable(K, V): iterable<NK, NV> $mapper
     * @return static<NK, NV>
     */
    public function flatMapKV(callable $mapper): static
    {
        $map = new static();

        foreach ($this->getIterator() as $key => $value) {
            foreach ($mapper($key, $value) as $newKey => $newValue) {
                /** @psalm-suppress InvalidArgument */
                $map->put($newKey, $newValue);
            }
        }

        return $map;
    }

    public function slice(int $offset, ?int $length = null): static
    {
        if ($offset < 0) {
            $offset = $this->count() + $offset;
        }

        $rightOffset = match (true) {
            $length === null => null,
            $length < 0 => $this->count() + $length,
            default => $offset + $length,
        };

        return $this->filter(
            static function () use ($offset, $rightOffset): bool {
                /** @var int */
                static $index = -1;
                ++$index;

                return $index >= $offset && ($rightOffset === null || $index < $rightOffset);
            },
        );
    }
}
