<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\ArrayMap;

/**
 * @api
 * @template-covariant K
 * @template-covariant V
 * @implements \IteratorAggregate<K, V>
 * @implements \ArrayAccess<mixed, mixed>
 * @psalm-consistent-templates
 * @psalm-suppress InvalidTemplateParam
 */
abstract class Map implements \IteratorAggregate, \Countable, \ArrayAccess
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
     * @param iterable<NK> $keys
     * @param callable(NK): NV $value
     * @return self<NK, NV>
     */
    public static function fromKeys(iterable $keys, callable $value): self
    {
        return ArrayMap::fromKeys($keys, $value);
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
        return ArrayMap::fromValues($values, $key);
    }

    /**
     * @template NK
     * @template NV
     * @param NK $key
     * @param NV $value
     * @return static<K|NK, V|NV>
     */
    abstract public function with(mixed $key, mixed $value): static;

    /**
     * @template NK
     * @template NV
     * @param KVPair<NK, NV> ...$kvPairs
     * @return static<K|NK, V|NV>
     */
    abstract public function withPairs(KVPair ...$kvPairs): static;

    /**
     * @template NK
     * @template NV
     * @param iterable<NK, NV>|\Closure(): iterable<NK, NV> $values
     * @return static<K|NK, V|NV>
     */
    abstract public function withAll(iterable|\Closure $values): static;

    /**
     * @return static<K, V>
     */
    abstract public function without(mixed ...$keys): static;

    abstract public function isEmpty(): bool;

    /**
     * @return non-negative-int
     */
    abstract public function count(): int;

    /**
     * @return ($key is K ? bool : false)
     */
    abstract public function contains(mixed $key): bool;

    /**
     * @template D
     * @param D $default
     * @return ($key is K ? V|D : D)
     */
    final public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->getOr($key, static fn(): mixed => $default);
    }

    /**
     * @template D
     * @param callable(): D $or
     * @return ($key is K ? V|D : D)
     */
    abstract public function getOr(mixed $key, callable $or): mixed;

    /**
     * @return ?KVPair<K, V>
     */
    abstract public function first(): ?KVPair;

    /**
     * @return ?KVPair<K, V>
     */
    abstract public function last(): ?KVPair;

    /**
     * @param callable(V): bool $predicate
     * @return ?KVPair<K, V>
     */
    final public function findFirst(callable $predicate): ?KVPair
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($predicate($value)) {
                return new KVPair($key, $value);
            }
        }

        return null;
    }

    /**
     * @param callable(K, V): bool $predicate
     * @return ?KVPair<K, V>
     */
    final public function findFirstKV(callable $predicate): ?KVPair
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($predicate($key, $value)) {
                return new KVPair($key, $value);
            }
        }

        return null;
    }

    /**
     * @param callable(V): bool $predicate
     */
    final public function any(callable $predicate): bool
    {
        foreach ($this->getIterator() as $value) {
            if ($predicate($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(K, V): bool $predicate
     */
    final public function anyKV(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($predicate($key, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(V): bool $predicate
     */
    final public function all(callable $predicate): bool
    {
        foreach ($this->getIterator() as $value) {
            if (!$predicate($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable(K, V): bool $predicate
     */
    final public function allKV(callable $predicate): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if (!$predicate($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @template R
     * @param callable(V|R, V): R $operation
     * @return V|R
     */
    final public function reduce(callable $operation): mixed
    {
        return $this->reduceKV(
            /**
             * @param V|R $accumulator
             * @param V $value
             */
            static fn (mixed $accumulator, mixed $key, mixed $value): mixed => $operation($accumulator, $value)
        );
    }

    /**
     * @template R
     * @param callable(V|R, K, V): R $operation
     * @return V|R
     */
    abstract public function reduceKV(callable $operation): mixed;

    /**
     * @template I
     * @template R
     * @param I $initial
     * @param callable(I|R, V): R $operation
     * @return I|R
     */
    final public function fold(mixed $initial, callable $operation): mixed
    {
        return $this->foldKV(
            $initial,
            /**
             * @param I|R $accumulator
             * @param V $value
             */
            static fn (mixed $accumulator, mixed $key, mixed $value): mixed => $operation($accumulator, $value)
        );
    }

    /**
     * @template I
     * @template R
     * @param I $initial
     * @param callable(I|R, K, V): R $operation
     * @return I|R
     */
    abstract public function foldKV(mixed $initial, callable $operation): mixed;

    /**
     * @param callable(V): bool $predicate
     * @return static<K, V>
     */
    final public function filter(callable $predicate): static
    {
        return $this->filterKV(
            /** @param V $value */
            static fn(mixed $key, mixed $value): bool => $predicate($value),
        );
    }

    /**
     * @param callable(K, V): bool $predicate
     * @return static<K, V>
     */
    abstract public function filterKV(callable $predicate): static;

    /**
     * @template NV
     * @param callable(V): NV $mapper
     * @return static<K, NV>
     */
    final public function map(callable $mapper): static
    {
        return $this->mapKV(
            /** @param V $value */
            static fn(mixed $key, mixed $value): mixed => $mapper($value),
        );
    }

    /**
     * @template NV
     * @param callable(K, V): NV $mapper
     * @return static<K, NV>
     */
    abstract public function mapKV(callable $mapper): static;

    /**
     * @template NK
     * @param callable(V): NK $mapper
     * @return static<NK, V>
     */
    final public function reindex(callable $mapper): static
    {
        return $this->reindexKV(
            /** @param V $value */
            static fn(mixed $key, mixed $value): mixed => $mapper($value),
        );
    }

    /**
     * @template NK
     * @param callable(K, V): NK $mapper
     * @return static<NK, V>
     */
    abstract public function reindexKV(callable $mapper): static;

    /**
     * @return static<V, K>
     */
    abstract public function flip(): static;

    /**
     * @return static<K, V>
     */
    abstract public function reverse(): static;

    /**
     * @return static<K, V>
     */
    final public function sort(): static
    {
        return $this->usortKV(static fn(mixed $key1, mixed $value1, mixed $key2, mixed $value2): int => $value1 <=> $value2);
    }

    /**
     * @return static<K, V>
     */
    final public function sortDesc(): static
    {
        return $this->usortKV(static fn(mixed $key1, mixed $value1, mixed $key2, mixed $value2): int => $value2 <=> $value1);
    }

    /**
     * @return static<K, V>
     */
    final public function ksort(): static
    {
        return $this->usortKV(static fn(mixed $key1, mixed $value1, mixed $key2): int => $key1 <=> $key2);
    }

    /**
     * @return static<K, V>
     */
    final public function ksortDesc(): static
    {
        return $this->usortKV(static fn(mixed $key1, mixed $value1, mixed $key2): int => $key2 <=> $key1);
    }

    /**
     * @param callable(V, V): int $comparator
     * @return static<K, V>
     */
    final public function usort(callable $comparator): static
    {
        return $this->usortKV(
            /**
             * @param V $value1
             * @param V $value2
             */
            static fn(mixed $key1, mixed $value1, mixed $key2, mixed $value2): int => $comparator($value1, $value2),
        );
    }

    /**
     * @param callable(K, V, K, V): int $comparator
     * @return static<K, V>
     */
    abstract public function usortKV(callable $comparator): static;

    /**
     * @return static<K, V>
     */
    abstract public function slice(int $offset, ?int $length = null): static;

    /**
     * @return Sequence<K>
     */
    abstract public function keys(): Sequence;

    /**
     * @return Sequence<V>
     */
    abstract public function values(): Sequence;

    /**
     * @return Sequence<KVPair<K, V>>
     */
    abstract public function pairs(): Sequence;

    /**
     * @return (K is array-key ? array<K, V>: never)
     */
    abstract public function toArray(): array;

    /**
     * @return ($offset is K ? bool : false)
     */
    final public function offsetExists(mixed $offset): bool
    {
        return $this->contains($offset);
    }

    /**
     * @return ($offset is K ? V : never)
     * @psalm-suppress InvalidReturnType, NoValue
     */
    final public function offsetGet(mixed $offset): mixed
    {
        return $this->getOr($offset, static fn(): never => throw new KeyIsNotDefined($offset));
    }

    final public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException();
    }

    final public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException();
    }
}
