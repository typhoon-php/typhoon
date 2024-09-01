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
     * @param KeyValue<NK, NV> ...$keyValues
     * @return self<NK, NV>
     */
    public static function ofKV(KeyValue ...$keyValues): self
    {
        return ArrayMap::ofKV(...$keyValues);
    }

    /**
     * @template NK
     * @template NV
     * @param NK $key
     * @param NV $value
     * @return static<K|NK, V|NV>
     */
    final public function with(mixed $key, mixed $value): static
    {
        return $this->withKV(new KeyValue($key, $value));
    }

    /**
     * @template NK
     * @template NV
     * @param KeyValue<NK, NV> ...$keyValues
     * @return static<K|NK, V|NV>
     */
    abstract public function withKV(KeyValue ...$keyValues): static;

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
     * @return ?KeyValue<K, V>
     */
    abstract public function first(): ?KeyValue;

    /**
     * @return ?KeyValue<K, V>
     */
    abstract public function last(): ?KeyValue;

    /**
     * @param callable(V): bool $predicate
     * @return ?KeyValue<K, V>
     */
    final public function findFirst(callable $predicate): ?KeyValue
    {
        return $this->findFirstKV(
            /** @param KeyValue<K, V> $keyValue */
            static fn(KeyValue $keyValue): bool => $predicate($keyValue->value),
        );
    }

    /**
     * @param callable(KeyValue<K, V>): bool $predicate
     * @return ?KeyValue<K, V>
     */
    abstract public function findFirstKV(callable $predicate): ?KeyValue;

    /**
     * @param callable(V): bool $predicate
     */
    final public function any(callable $predicate): bool
    {
        return $this->anyKV(
            /** @param KeyValue<K, V> $keyValue */
            static fn(KeyValue $keyValue): bool => $predicate($keyValue->value),
        );
    }

    /**
     * @param callable(KeyValue<K, V>): bool $predicate
     */
    abstract public function anyKV(callable $predicate): bool;

    /**
     * @param callable(V): bool $predicate
     */
    final public function all(callable $predicate): bool
    {
        return $this->allKV(
            /** @param KeyValue<K, V> $keyValue */
            static fn(KeyValue $keyValue): bool => $predicate($keyValue->value),
        );
    }

    /**
     * @param callable(KeyValue<K, V>): bool $predicate
     */
    abstract public function allKV(callable $predicate): bool;

    /**
     * @template I
     * @template R
     * @param callable(I|R, V): R $reducer
     * @param I $initial
     * @return I|R
     */
    public function reduce(callable $reducer, mixed $initial = null): mixed
    {
        return $this->reduceKV(
            /**
             * @param I|R $carry
             * @param KeyValue<K, V> $keyValue
             */
            static fn(mixed $carry, KeyValue $keyValue): mixed => $reducer($carry, $keyValue->value),
            $initial,
        );
    }

    /**
     * @template I
     * @template R
     * @param callable(I|R, KeyValue<K, V>): R $reducer
     * @param I $initial
     * @return I|R
     */
    abstract public function reduceKV(callable $reducer, mixed $initial = null): mixed;

    /**
     * @param callable(V): bool $predicate
     * @return static<K, V>
     */
    final public function filter(callable $predicate): static
    {
        return $this->filterKV(
            /** @param KeyValue<K, V> $keyValue */
            static fn(KeyValue $keyValue): bool => $predicate($keyValue->value),
        );
    }

    /**
     * @param callable(KeyValue<K, V>): bool $predicate
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
            /** @param KeyValue<K, V> $keyValue */
            static fn(KeyValue $keyValue): KeyValue => $keyValue->withValue($mapper($keyValue->value)),
        );
    }

    /**
     * @template NK
     * @template NV
     * @param callable(KeyValue<K, V>): KeyValue<NK, NV> $mapper
     * @return static<NK, NV>
     */
    abstract public function mapKV(callable $mapper): static;

    /**
     * @return static<V, K>
     */
    final public function flip(): static
    {
        return $this->mapKV(static fn(KeyValue $keyValue): KeyValue => $keyValue->flip());
    }

    /**
     * @return static<K, V>
     */
    abstract public function reverse(): static;

    /**
     * @return static<K, V>
     */
    final public function sort(): static
    {
        return $this->usortKV(static fn(KeyValue $kv1, KeyValue $kv2): int => $kv1->value <=> $kv2->value);
    }

    /**
     * @return static<K, V>
     */
    final public function sortDesc(): static
    {
        return $this->usortKV(static fn(KeyValue $kv1, KeyValue $kv2): int => $kv2->value <=> $kv1->value);
    }

    /**
     * @return static<K, V>
     */
    final public function ksort(): static
    {
        return $this->usortKV(static fn(KeyValue $kv1, KeyValue $kv2): int => $kv1->key <=> $kv2->key);
    }

    /**
     * @return static<K, V>
     */
    final public function ksortDesc(): static
    {
        return $this->usortKV(static fn(KeyValue $kv1, KeyValue $kv2): int => $kv2->key <=> $kv1->key);
    }

    /**
     * @param callable(V, V): int $comparator
     * @return static<K, V>
     */
    final public function usort(callable $comparator): static
    {
        return $this->usortKV(
            /**
             * @param KeyValue<K, V> $keyValue1
             * @param KeyValue<K, V> $keyValue2
             */
            static fn(KeyValue $keyValue1, KeyValue $keyValue2): int => $comparator($keyValue1->value, $keyValue2->value),
        );
    }

    /**
     * @param callable(KeyValue<K, V>, KeyValue<K, V>): int $comparator
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
     * @return Sequence<KeyValue<K, V>>
     */
    abstract public function keyValues(): Sequence;

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
