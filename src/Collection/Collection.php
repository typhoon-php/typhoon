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
     * @var array<array{TKey, TValue}>
     */
    private array $values = [];

    /**
     * @param iterable<TKey, TValue> $values
     */
    public function __construct(iterable $values = [])
    {
        foreach ($values as $key => $value) {
            $this->values[KeyHasher::hash($key)] = [$key, $value];
        }
    }

    /**
     * @template TTKey
     * @template TTValue
     * @param iterable<array{TTKey, TTValue}> $keyValuePairs
     * @return self<TTKey, TTValue>
     */
    public static function fromKeyValuePairs(iterable $keyValuePairs): self
    {
        /** @var self<TTKey, TTValue> */
        $collection = new self();

        foreach ($keyValuePairs as $keyValuePair) {
            $collection->values[KeyHasher::hash($keyValuePair[0])] = $keyValuePair;
        }

        return $collection;
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
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $mapper
     * @return self<TKey, TNewValue>
     * @psalm-suppress UnusedVariable, InvalidReturnType, InvalidReturnStatement
     */
    public function map(callable $mapper): self
    {
        $collection = clone $this;

        foreach ($collection->values as [$key, &$value]) {
            $value = $mapper($value, $key);
        }

        return $collection;
    }

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return self<TKey, TValue>
     */
    public function filter(callable $filter): self
    {
        $collection = clone $this;

        foreach ($collection->values as $hash => [$key, $value]) {
            if (!$filter($value, $key)) {
                unset($collection->values[$hash]);
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
        return new self($this->toList());
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
