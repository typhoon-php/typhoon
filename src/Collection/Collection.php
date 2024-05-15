<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 * @psalm-suppress InvalidTemplateParam
 * @template TKey
 * @template-covariant TValue
 * @implements \ArrayAccess<TKey, TValue>
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class Collection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array<string, array{TKey, TValue}>
     */
    private array $values = [];

    /**
     * @param iterable<array{TKey, TValue}> $values
     */
    public function __construct(iterable $values = [])
    {
        foreach ($values as [$key, $value]) {
            $this->values[Hasher::hash($key)] = [$key, $value];
        }
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
     * @param TKey $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[Hasher::hash($offset)]);
    }

    /**
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[Hasher::hash($offset)][1] ?? throw new \RuntimeException();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException();
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException();
    }

    /**
     * @return \Generator<TKey, TValue>
     */
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
}
