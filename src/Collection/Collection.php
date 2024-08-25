<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 * @template TKey
 * @template-covariant TValue
 *
 * It is valid to implement ArrayAccess with a covariant TValue, because we do not allow to call mutating offsetSet()
 * and offsetUnset() methods.
 * @psalm-suppress InvalidTemplateParam
 * @implements \ArrayAccess<TKey, TValue>
 *
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class Collection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array<int|non-empty-string, array{TKey, TValue}>
     */
    private array $values = [];

    /**
     * @param iterable<TKey, TValue>|callable(): iterable<TKey, TValue> $values
     */
    public function __construct(iterable|callable $values = [])
    {
        if (\is_callable($values)) {
            $values = $values();
        }

        foreach ($values as $key => $value) {
            $this->values[KeyHasher::hash($key)] = [$key, $value];
        }
    }

    /**
     * @template TNewKey
     * @template TNewValue
     * @param iterable<array{TNewKey, TNewValue}> $kvPairs
     * @return self<TNewKey, TNewValue>
     */
    public static function fromKVPairs(iterable $kvPairs): self
    {
        /** @var self<TNewKey, TNewValue> */
        $collection = new self();

        foreach ($kvPairs as $kvPair) {
            $collection->values[KeyHasher::hash($kvPair[0])] = $kvPair;
        }

        return $collection;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T): non-empty-string $hasher
     */
    public static function registerObjectHasher(string $class, callable $hasher): void
    {
        KeyHasher::registerObjectHasher($class, $hasher);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[KeyHasher::hash($offset)]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        /** @var TValue */
        return $this->values[KeyHasher::hash($offset)][1] ?? throw new KeyIsNotDefined($offset);
    }

    /**
     * @template TNewKey
     * @template TNewValue
     * @param TNewKey $key
     * @param TNewValue $value
     * @return self<TKey|TNewKey, TValue|TNewValue>
     */
    public function with(mixed $key, mixed $value): self
    {
        /** @var self<TKey|TNewKey, TValue|TNewValue> */
        $collection = clone $this;
        $collection->values[KeyHasher::hash($key)] = [$key, $value];

        return $collection;
    }

    /**
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $mapper
     * @return self<TKey, TNewValue>
     */
    public function map(callable $mapper): self
    {
        /** @var self<TKey, TNewValue> */
        $collection = new self();

        foreach ($this->values as $hash => [$key, $value]) {
            $collection->values[$hash] = [$key, $mapper($value, $key)];
        }

        return $collection;
    }

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return self<TKey, TValue>
     */
    public function filter(callable $filter): self
    {
        /** @var self<TKey, TValue> */
        $collection = new self();

        foreach ($this->values as $hash => [$key, $value]) {
            if ($filter($value, $key)) {
                $collection->values[$hash] = [$key, $value];
            }
        }

        return $collection;
    }

    /**
     * @return list<TKey>
     */
    public function keys(): array
    {
        return array_column($this->values, 0);
    }

    /**
     * @return ?TKey
     */
    public function firstKey(): mixed
    {
        $hash = array_key_first($this->values);

        if ($hash === null) {
            return null;
        }

        return $this->values[$hash][0];
    }

    /**
     * @return ?TKey
     */
    public function lastKey(): mixed
    {
        $hash = array_key_last($this->values);

        if ($hash === null) {
            return null;
        }

        return $this->values[$hash][0];
    }

    /**
     * @return ?TValue
     */
    public function first(): mixed
    {
        $hash = array_key_first($this->values);

        if ($hash === null) {
            return null;
        }

        return $this->values[$hash][1];
    }

    /**
     * @return ?TValue
     */
    public function last(): mixed
    {
        $hash = array_key_last($this->values);

        if ($hash === null) {
            return null;
        }

        return $this->values[$hash][1];
    }

    /**
     * @return self<non-negative-int, TValue>
     */
    public function toIndexed(): self
    {
        /** @var self<non-negative-int, TValue> */
        $collection = new self();
        $index = 0;

        foreach ($this->values as [, $value]) {
            $collection->values[] = [$index++, $value];
        }

        return $collection;
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return array_column($this->values, 1, 0);
    }

    /**
     * @return list<TValue>
     */
    public function toList(): array
    {
        return array_column($this->values, 1);
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->values as [$key, $value]) {
            if ($predicate($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     */
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

    /**
     * @return non-negative-int
     */
    public function count(): int
    {
        return \count($this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException(\sprintf('%s is immutable', self::class));
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException(\sprintf('%s is immutable', self::class));
    }
}
