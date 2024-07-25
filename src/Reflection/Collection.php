<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use function Typhoon\Reflection\Internal\array_value_first;
use function Typhoon\Reflection\Internal\array_value_last;

/**
 * @api
 *
 * @template TKey of array-key
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
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param array<TKey, TValue> $values
     */
    public function __construct(
        private readonly array $values,
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
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $mapper
     * @return self<TKey, TNewValue>
     */
    public function map(callable $mapper): self
    {
        $values = [];

        foreach ($this->values as $name => $value) {
            $values[$name] = $mapper($value, $name);
        }

        return new self($values);
    }

    /**
     * @param callable(TValue, TKey): bool $filter
     * @return self<TKey, TValue>
     */
    public function filter(callable $filter): self
    {
        $values = [];

        foreach ($this->values as $name => $value) {
            if ($filter($value, $name)) {
                $values[$name] = $value;
            }
        }

        return new self($values);
    }

    /**
     * @return list<TKey>
     */
    public function keys(): array
    {
        return array_keys($this->values);
    }

    /**
     * @return ?TKey
     */
    public function firstKey(): null|int|string
    {
        return array_key_first($this->values);
    }

    /**
     * @return ?TKey
     */
    public function lastKey(): null|int|string
    {
        return array_key_last($this->values);
    }

    /**
     * @return ?TValue
     */
    public function first(): mixed
    {
        return array_value_first($this->values);
    }

    /**
     * @return ?TValue
     */
    public function last(): mixed
    {
        return array_value_last($this->values);
    }

    /**
     * @return self<non-negative-int, TValue>
     */
    public function toIndexed(): self
    {
        return new self(array_values($this->values));
    }

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @return list<TValue>
     */
    public function toList(): array
    {
        return array_values($this->values);
    }

    /**
     * @param callable(TValue, TKey): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        foreach ($this->values as $name => $value) {
            if ($predicate($value, $name)) {
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
        foreach ($this->values as $name => $value) {
            if (!$predicate($value, $name)) {
                return false;
            }
        }

        return true;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->values);
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    /**
     * @return non-negative-int
     */
    public function count(): int
    {
        return \count($this->values);
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
