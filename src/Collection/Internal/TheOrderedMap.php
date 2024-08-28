<?php

declare(strict_types=1);

namespace Typhoon\Collection\Internal;

use Typhoon\Collection\KeyIsNotDefined;
use Typhoon\Collection\List_;
use Typhoon\Collection\MutableOrderedMap;
use Typhoon\Collection\OrderedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Collection
 * @template TKey
 * @template TValue
 * @implements MutableOrderedMap<TKey, TValue>
 */
final class TheOrderedMap implements MutableOrderedMap
{
    /**
     * @param array<int|non-empty-string, array{TKey, TValue}> $values
     */
    private function __construct(
        private array $values = [],
    ) {}

    /**
     * @template TNewKey
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $values
     * @return self<TNewKey, TNewValue>
     */
    public static function fromValues(iterable $values): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($values instanceof self) {
            return new self($values->values);
        }

        $collection = new self();

        foreach ($values as $key => $value) {
            $collection->set($key, $value);
        }

        return $collection;
    }

    public function set(mixed $key, mixed $value): void
    {
        $this->values[KeyEncoder::encode($key)] = [$key, $value];
    }

    public function unset(mixed ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->values[KeyEncoder::encode($key)]);
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[KeyEncoder::encode($offset)]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[KeyEncoder::encode($offset)][1] ?? throw new KeyIsNotDefined($offset);
    }

    public function with(mixed $key, mixed $value): static
    {
        $collection = clone $this;
        $collection->set($key, $value);

        return $collection;
    }

    public function without(mixed ...$keys): static
    {
        $collection = clone $this;
        $collection->unset(...$keys);

        return $collection;
    }

    /**
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $mapper
     * @return static<TKey, TNewValue>
     */
    public function map(callable $mapper): static
    {
        /** @var static<TKey, TNewValue> */
        $collection = new self();

        foreach ($this->values as $encodedKey => [$key, $value]) {
            $collection->values[$encodedKey] = [$key, $mapper($value, $key)];
        }

        return $collection;
    }

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return static<TKey, TValue>
     */
    public function filter(callable $filter): static
    {
        /** @var static<TKey, TValue> */
        $collection = new self();

        foreach ($this->values as $encodedKey => [$key, $value]) {
            if ($filter($value, $key)) {
                $collection->values[$encodedKey] = [$key, $value];
            }
        }

        return $collection;
    }

    public function keys(): List_
    {
        return new TheList(array_column($this->values, 0));
    }

    public function firstKey(): mixed
    {
        $encodedKey = array_key_first($this->values);

        if ($encodedKey === null) {
            return null;
        }

        return $this->values[$encodedKey][0];
    }

    public function lastKey(): mixed
    {
        $encodedKey = array_key_last($this->values);

        if ($encodedKey === null) {
            return null;
        }

        return $this->values[$encodedKey][0];
    }

    public function firstValue(): mixed
    {
        $encodedKey = array_key_first($this->values);

        if ($encodedKey === null) {
            return null;
        }

        return $this->values[$encodedKey][1];
    }

    public function lastValue(): mixed
    {
        $encodedKey = array_key_last($this->values);

        if ($encodedKey === null) {
            return null;
        }

        return $this->values[$encodedKey][1];
    }

    public function values(): List_
    {
        return new TheList(array_column($this->values, 1));
    }

    /**
     * @return (TKey is array-key ? array<TKey, TValue> : never)
     */
    public function toArray(): array
    {
        return array_column($this->values, 1, 0);
    }

    public function keyValuePairs(): List_
    {
        return new TheList(array_values($this->values));
    }

    public function any(callable $predicate): bool
    {
        foreach ($this->values as [$key, $value]) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    public function all(callable $predicate): bool
    {
        foreach ($this->values as [$key, $value]) {
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        return true;
    }

    public function getIterator(): \Generator
    {
        foreach ($this->values as [$key, $value]) {
            yield $key => $value;
        }
    }

    public function count(): int
    {
        return \count($this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    public function sort(int $flags = SORT_REGULAR): static
    {
        $values = array_map(static fn(array $keyValuePair): mixed => $keyValuePair[1], $this->values);
        asort($values, $flags);

        /** @var self<TKey, TValue> */
        $collection = new self();

        foreach ($values as $encodedKey => $value) {
            $collection->values[$encodedKey] = [$this->values[$encodedKey][0], $value];
        }

        return $collection;
    }

    public function sortDesc(int $flags = SORT_REGULAR): static
    {
        $values = array_map(static fn(array $keyValuePair): mixed => $keyValuePair[1], $this->values);
        arsort($values, $flags);

        /** @var self<TKey, TValue> */
        $collection = new self();

        foreach ($values as $encodedKey => $value) {
            $collection->values[$encodedKey] = [$this->values[$encodedKey][0], $value];
        }

        return $collection;
    }

    public function sortBy(callable $comparator): static
    {
        $collection = clone $this;

        uasort(
            $collection->values,
            /**
             * @param array{TKey, TValue} $a
             * @param array{TKey, TValue} $b
             */
            static fn(array $a, array $b): int => $comparator($a[1], $b[1], $a[0], $b[0]),
        );

        return $collection;
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new self(\array_slice($this->values, $offset, $length, preserve_keys: true));
    }

    /**
     * @template TReturn
     * @template TInitial
     * @param callable(TReturn|TInitial, TValue, TKey): TReturn $reducer
     * @param TInitial $initial
     * @return TReturn|TInitial
     */
    public function reduce(callable $reducer, mixed $initial = null): mixed
    {
        return array_reduce(
            $this->values,
            /**
             * @param TReturn|TInitial $carry
             * @param array{TKey, TValue} $keyValue
             * @return TReturn
             */
            static fn(mixed $carry, array $keyValue): mixed => $reducer($carry, $keyValue[1], $keyValue[0]),
            $initial,
        );
    }

    public function reverse(): static
    {
        return new self(array_reverse($this->values, preserve_keys: true));
    }

    public function flip(): static
    {
        /** @var self<TValue, TKey> */
        $collection = new self();

        foreach ($this->values as $encodedKey => [$key, $value]) {
            $collection->values[$encodedKey] = [$value, $key];
        }

        return $collection;
    }

    public function find(callable $filter): mixed
    {
        foreach ($this->values as [$key, $value]) {
            if ($filter($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    public function findKey(callable $filter): mixed
    {
        foreach ($this->values as [$key, $value]) {
            if ($filter($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException();
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException();
    }

    public function toMutable(): OrderedMap
    {
        return clone $this;
    }

    /**
     * @return list<array{TKey, TValue}>
     */
    public function __serialize(): array
    {
        return array_values($this->values);
    }

    /**
     * @param list<array{TKey, TValue}> $keyValuePairs
     */
    public function __unserialize(array $keyValuePairs): void
    {
        foreach ($keyValuePairs as $keyValuePair) {
            $this->values[KeyEncoder::encode($keyValuePair[0])] = $keyValuePair;
        }
    }
}
