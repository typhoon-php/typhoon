<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 * @template TKey of array-key|object
 * @template-covariant TValue
 *
 * It is valid to implement ArrayAccess with a covariant TValue, because we do not allow to call mutating offsetSet()
 * and offsetUnset() methods.
 * @psalm-suppress InvalidTemplateParam
 * @implements \ArrayAccess<TKey, TValue>
 *
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class CollectionSplit implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array<TValue>
     */
    private array $array = [];

    /**
     * @var \SplObjectStorage<object, TValue>
     */
    private \SplObjectStorage $objectStorage;

    /**
     * @var list<TKey>
     */
    private array $keys = [];

    /**
     * @param iterable<TKey, TValue> $values
     */
    public function __construct(iterable $values = [])
    {
        /** @var \SplObjectStorage<object, TValue> */
        $this->objectStorage = new \SplObjectStorage();

        foreach ($values as $key => $value) {
            $this->keys[] = $key;

            if (\is_object($key)) {
                $this->objectStorage->attach($key, $value);
            } else {
                $this->array[$key] = $value;
            }
        }
    }

    /**
     * @template TTKey of array-key|object
     * @template TTValue
     * @param iterable<array{TTKey, TTValue}> $keyValuePairs
     * @return self<TTKey, TTValue>
     */
    public static function fromKeyValuePairs(iterable $keyValuePairs): self
    {
        /** @var self<TTKey, TTValue> */
        $collection = new self();

        foreach ($keyValuePairs as [$key, $value]) {
            $collection->keys[] = $key;

            if (\is_object($key)) {
                $collection->objectStorage->attach($key, $value);
            } else {
                $collection->array[$key] = $value;
            }
        }

        return $collection;
    }

    public function offsetExists(mixed $offset): bool
    {
        if (\is_object($offset)) {
            return $this->objectStorage->contains($offset);
        }

        return isset($this->array[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (\is_object($offset)) {
            return $this->objectStorage->offsetGet($offset) ?? throw new KeyIsNotDefined($offset);
        }

        /** @psalm-suppress DocblockTypeContradiction */
        return $this->array[$offset] ?? throw new KeyIsNotDefined($offset);
    }

    /**
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $mapper
     * @return self<TKey, TNewValue>
     */
    public function map(callable $mapper): self
    {
        /** @var self<TKey, TNewValue> */
        $new = new self();

        foreach ($this->array as $key => $value) {
            /**
             * @psalm-suppress InvalidPropertyAssignmentValue
             * @var TKey $key
             */
            $new->array[$key] = $mapper($value, $key);
        }

        foreach ($this->objectStorage as $key) {
            /**
             * @psalm-suppress PossiblyInvalidArgument
             * @var TKey $key
             */
            $new->objectStorage->attach($key, $mapper($this->objectStorage->getInfo(), $key));
        }

        return $new;
    }

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return self<TKey, TValue>
     */
    public function filter(callable $filter): self
    {
        /** @var self<TKey, TValue> */
        $new = new self();

        foreach ($this->array as $key => $value) {
            /** @var TKey $key */
            if ($filter($value, $key)) {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $new->array[$key] = $value;
            }
        }

        foreach ($this->objectStorage as $key) {
            /** @var TKey $key */
            $value = $new->objectStorage->getInfo();

            if ($filter($value, $key)) {
                /** @psalm-suppress PossiblyInvalidArgument */
                $new->objectStorage->attach($key, $value);
            }
        }

        return $new;
    }

    /**
     * @return list<TKey>
     */
    public function keys(): array
    {
        return $this->keys;
    }

    /**
     * @return ?TKey
     */
    public function firstKey(): mixed
    {
        return $this->keys[0] ?? null;
    }

    /**
     * @return ?TKey
     */
    public function lastKey(): mixed
    {
        $count = \count($this->keys);

        if ($count === 0) {
            return null;
        }

        return $this->keys[$count - 1];
    }

    /**
     * @return ?TValue
     */
    public function first(): mixed
    {
        if (isset($this->keys[0])) {
            return $this->offsetGet($this->keys[0]);
        }

        return null;
    }

    /**
     * @return ?TValue
     */
    public function last(): mixed
    {
        $count = \count($this->keys);

        if ($count === 0) {
            return null;
        }

        return $this->offsetGet($this->keys[$count - 1]);
    }

    /**
     * @return self<non-negative-int, TValue>
     */
    public function toIndexed(): self
    {
        return new self($this->toList());
    }

    /**
     * @return (TKey is array-key ? array<TKey, TValue> : never)
     * @psalm-suppress MismatchingDocblockReturnType, MixedInferredReturnType, UnusedPsalmSuppress
     */
    public function toArray(): array
    {
        if ($this->objectStorage->count() > 0) {
            throw new \RuntimeException();
        }

        /** @var array<TKey, TValue> */
        return $this->array;
    }

    /**
     * @return list<TValue>
     */
    public function toList(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->array as $key => $value) {
            /** @var TKey $key */
            if ($predicate($value, $key)) {
                return true;
            }
        }

        foreach ($this->objectStorage as $key) {
            /** @var TKey $key */
            if ($predicate($this->objectStorage->getInfo(), $key)) {
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
        foreach ($this->array as $key => $value) {
            /** @var TKey $key */
            if (!$predicate($value, $key)) {
                return false;
            }
        }

        foreach ($this->objectStorage as $key) {
            /** @var TKey $key */
            if (!$predicate($this->objectStorage->getInfo(), $key)) {
                return false;
            }
        }

        return true;
    }

    public function getIterator(): \Generator
    {
        if ($this->array === []) {
            foreach ($this->objectStorage as $key) {
                yield $key => $this->objectStorage->getInfo();
            }

            return;
        }

        if ($this->objectStorage->count() === 0) {
            yield from $this->array;

            return;
        }

        foreach ($this->keys as $key) {
            yield $key => $this->offsetGet($key);
        }
    }

    /**
     * @return non-negative-int
     */
    public function count(): int
    {
        return \count($this->keys);
    }

    public function isEmpty(): bool
    {
        return $this->keys === [];
    }

    public function __clone()
    {
        $this->objectStorage = clone $this->objectStorage;
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
