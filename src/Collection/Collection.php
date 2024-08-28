<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 * @template TKey
 * @template-covariant TValue
 * @extends \IteratorAggregate<TKey, TValue>
 * @extends \ArrayAccess<TKey, TValue>
 * It is valid to implement ArrayAccess with a covariant TValue, because we do not allow to call mutating offsetSet()
 * and offsetUnset() methods.
 * @psalm-suppress InvalidTemplateParam
 */
interface Collection extends \ArrayAccess, \IteratorAggregate, \Countable
{
    public function offsetSet(mixed $offset, mixed $value): never;

    public function offsetUnset(mixed $offset): never;

    /**
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $mapper
     * @return static<TKey, TNewValue>
     */
    public function map(callable $mapper): static;

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return static<TKey, TValue>
     */
    public function filter(callable $filter): static;

    /**
     * @return List_<TKey>
     */
    public function keys(): List_;

    /**
     * @return ?TKey
     */
    public function firstKey(): mixed;

    /**
     * @return ?TKey
     */
    public function lastKey(): mixed;

    /**
     * @return ?TValue
     */
    public function firstValue(): mixed;

    /**
     * @return ?TValue
     */
    public function lastValue(): mixed;

    /**
     * @return List_<TValue>
     */
    public function values(): List_;

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array;

    /**
     * @return List_<array{TKey, TValue}>
     */
    public function keyValuePairs(): List_;

    /**
     * @param callable(TValue, TKey): bool $predicate
     */
    public function any(callable $predicate): bool;

    /**
     * @param callable(TValue, TKey): bool $predicate
     */
    public function all(callable $predicate): bool;

    /**
     * @return non-negative-int
     */
    public function count(): int;

    public function isEmpty(): bool;

    public function sort(int $flags = SORT_REGULAR): static;

    public function sortDesc(int $flags = SORT_REGULAR): static;

    /**
     * @param callable(TValue, TValue, TKey, TKey): int $comparator
     */
    public function sortBy(callable $comparator): static;

    public function slice(int $offset, ?int $length = null): static;

    /**
     * @template TReturn
     * @template TInitial
     * @param callable(TReturn|TInitial, TValue, TKey): TReturn $reducer
     * @param TInitial $initial
     * @return TReturn|TInitial
     */
    public function reduce(callable $reducer, mixed $initial = null): mixed;

    public function reverse(): static;

    /**
     * @return OrderedMap<TValue, TKey>
     */
    public function flip(): OrderedMap;

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return ?TValue
     */
    public function find(callable $filter): mixed;

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return ?TKey
     */
    public function findKey(callable $filter): mixed;

    /**
     * @return self<TKey, TValue>
     */
    public function toMutable(): self;
}
