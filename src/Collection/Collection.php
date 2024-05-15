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
     * @var ?array<string, array{TKey, TValue}>
     */
    private ?array $loadedValues = null;

    /**
     * @param iterable<TKey, TValue>|\Closure(): iterable<TKey, TValue> $values
     */
    public function __construct(
        private iterable|\Closure $values = [],
    ) {}

    /**
     * @template TNewKey
     * @template TNewValue
     * @param array{TNewKey, TNewValue} ...$keyValues
     * @return self<TNewKey, TNewValue>
     */
    public static function fromTuples(array ...$keyValues): self
    {
        /** @var self<TNewKey, TNewValue> */
        $collection = new self();
        $collection->loadedValues = [];

        foreach ($keyValues as [$key, $value]) {
            $collection->loadedValues[Hasher::hash($key)] = [$key, $value];
        }

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
        $collection->loadedValues = [];

        foreach ($this->loadValues() as $hash => [$key, $value]) {
            $collection->loadedValues[$hash] = [$key, $mapper($value, $key)];
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
        $collection->loadedValues = [];

        foreach ($this->loadValues() as $hash => [$key, $value]) {
            if ($filter($value, $key)) {
                $collection->loadedValues[$hash] = [$key, $value];
            }
        }

        return $collection;
    }

    /**
     * @param TKey $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->loadValues()[Hasher::hash($offset)]);
    }

    /**
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->loadValues()[Hasher::hash($offset)][1] ?? throw new \RuntimeException();
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
        foreach ($this->loadValues() as [$key, $value]) {
            yield $key => $value;
        }
    }

    /**
     * @return non-negative-int
     */
    public function count(): int
    {
        return \count($this->loadValues());
    }

    /**
     * @return array<string, array{TKey, TValue}>
     */
    private function loadValues(): array
    {
        if ($this->loadedValues !== null) {
            return $this->loadedValues;
        }

        $loadedValues = [];
        $values = $this->values instanceof \Closure ? ($this->values)() : $this->values;

        foreach ($values as $key => $value) {
            $loadedValues[Hasher::hash($key)] = [$key, $value];
        }

        $this->values = [];

        return $this->loadedValues = $loadedValues;
    }
}
