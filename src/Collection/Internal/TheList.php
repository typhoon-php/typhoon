<?php

declare(strict_types=1);

namespace Typhoon\Collection\Internal;

use Typhoon\Collection\KeyIsNotDefined;
use Typhoon\Collection\List_;
use Typhoon\Collection\MutableList;
use Typhoon\Collection\OrderedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Collection
 * @template TValue
 * @implements MutableList<TValue>
 */
final class TheList implements MutableList
{
    /**
     * @param list<TValue> $values
     */
    public function __construct(
        private array $values = [],
    ) {}

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset] ?? throw new KeyIsNotDefined($offset);
    }

    /**
     * @no-named-arguments
     */
    public function with(mixed ...$values): static
    {
        return new self([...$this->values, ...$values]);
    }

    public function map(callable $mapper): static
    {
        return new self(array_map(
            $mapper,
            $this->values,
            array_keys($this->values),
        ));
    }

    public function filter(callable $filter): static
    {
        return new self(array_values(array_filter($this->values, $filter, ARRAY_FILTER_USE_BOTH)));
    }

    public function keys(): static
    {
        return new self(array_keys($this->values));
    }

    public function firstKey(): mixed
    {
        return array_key_first($this->values);
    }

    public function lastKey(): mixed
    {
        return array_key_last($this->values);
    }

    public function firstValue(): mixed
    {
        return $this->values[0] ?? null;
    }

    public function lastValue(): mixed
    {
        $key = array_key_last($this->values);

        if ($key === null) {
            return null;
        }

        return $this->values[$key];
    }

    public function values(): List_
    {
        return clone $this;
    }

    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @psalm-suppress InvalidReturnType
     */
    public function keyValuePairs(): List_
    {
        /** @var list<array{non-negative-int, TValue}> */
        $keyValuePairs = array_map(null, array_keys($this->values), $this->values);

        return new self($keyValuePairs);
    }

    public function any(callable $predicate): bool
    {
        foreach ($this->values as $key => $value) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    public function all(callable $predicate): bool
    {
        foreach ($this->values as $key => $value) {
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        return true;
    }

    public function getIterator(): \Generator
    {
        yield from $this->values;
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
        $values = $this->values;
        sort($values, $flags);

        return new self($values);
    }

    public function sortDesc(int $flags = SORT_REGULAR): static
    {
        $values = $this->values;
        rsort($values, $flags);

        return new self($values);
    }

    public function sortBy(callable $comparator): static
    {
        // TODO
        throw new \LogicException();
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new self(\array_slice($this->values, $offset, $length));
    }

    public function reduce(callable $reducer, mixed $initial = null): mixed
    {
        // TODO
        throw new \LogicException();
    }

    public function reverse(): static
    {
        return new self(array_reverse($this->values));
    }

    public function flip(): OrderedMap
    {
        return TheOrderedMap::fromValues((function (): \Generator {
            foreach ($this->values as $key => $value) {
                yield $value => $key;
            }
        })());
    }

    public function find(callable $filter): mixed
    {
        foreach ($this->values as $key => $value) {
            if ($filter($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    public function findKey(callable $filter): mixed
    {
        foreach ($this->values as $key => $value) {
            if ($filter($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    public function toMutable(): List_
    {
        return clone $this;
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException();
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException();
    }

    /**
     * @return list<TValue>
     */
    public function __serialize(): array
    {
        return $this->values;
    }

    /**
     * @param list<TValue> $values
     */
    public function __unserialize(array $values): void
    {
        $this->values = $values;
    }
}
