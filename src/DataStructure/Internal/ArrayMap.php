<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use Typhoon\DataStructure\KeyValue;
use Typhoon\DataStructure\MutableMap;
use Typhoon\DataStructure\Sequence;

/**
 * @internal
 * @psalm-internal Typhoon\DataStructure
 * @template K
 * @template V
 * @extends MutableMap<K, V>
 */
final class ArrayMap extends MutableMap
{
    /**
     * @template NK
     * @template NV
     * @param iterable<NK, NV>|\Closure(): iterable<NK, NV> $values
     * @return self<NK, NV>
     */
    public static function of(iterable|\Closure $values = []): self
    {
        /** @var self<NK, NV> */
        $map = new self();
        $map->putAll($values);

        return $map;
    }

    /**
     * @template NK
     * @template NV
     * @param KeyValue<NK, NV> ...$keyValues
     * @return self<NK, NV>
     */
    public static function ofKV(KeyValue ...$keyValues): self
    {
        /** @var self<NK, NV> */
        $map = new self();
        $map->putKV(...$keyValues);

        return $map;
    }

    /**
     * @param array<KeyValue<K, V>> $keyValues
     */
    public function __construct(
        private array $keyValues = [],
    ) {}

    public function putKV(KeyValue ...$keyValues): void
    {
        foreach ($keyValues as $keyValue) {
            $this->keyValues[ArrayMapKeyEncoder::encode($keyValue->key)] = $keyValue;
        }
    }

    public function putAll(iterable|\Closure $values): void
    {
        if ($values instanceof \Closure) {
            $values = $values();
        }

        if ($values instanceof self) {
            $this->keyValues = [...$this->keyValues, ...$values->keyValues];

            return;
        }

        foreach ($values as $key => $value) {
            $this->keyValues[ArrayMapKeyEncoder::encode($key)] = new KeyValue($key, $value);
        }
    }

    public function remove(mixed ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->keyValues[ArrayMapKeyEncoder::encode($key)]);
        }
    }

    public function clear(): void
    {
        $this->keyValues = [];
    }

    public function isEmpty(): bool
    {
        return $this->keyValues === [];
    }

    public function count(): int
    {
        return \count($this->keyValues);
    }

    public function contains(mixed $key): bool
    {
        return isset($this->keyValues[ArrayMapKeyEncoder::encode($key)]);
    }

    public function getOr(mixed $key, callable $or): mixed
    {
        $encodedKey = ArrayMapKeyEncoder::encode($key);

        if (isset($this->keyValues[$encodedKey])) {
            return $this->keyValues[$encodedKey]->value;
        }

        return $or();
    }

    public function first(): ?KeyValue
    {
        $key = array_key_first($this->keyValues);

        if ($key === null) {
            return null;
        }

        return $this->keyValues[$key];
    }

    public function last(): ?KeyValue
    {
        $key = array_key_last($this->keyValues);

        if ($key === null) {
            return null;
        }

        return $this->keyValues[$key];
    }

    public function findFirstKV(callable $predicate): ?KeyValue
    {
        foreach ($this->keyValues as $keyValue) {
            if ($predicate($keyValue)) {
                return $keyValue;
            }
        }

        return null;
    }

    public function anyKV(callable $predicate): bool
    {
        foreach ($this->keyValues as $keyValue) {
            if ($predicate($keyValue)) {
                return true;
            }
        }

        return false;
    }

    public function allKV(callable $predicate): bool
    {
        foreach ($this->keyValues as $keyValue) {
            if (!$predicate($keyValue)) {
                return false;
            }
        }

        return true;
    }

    public function reduceKV(callable $reducer, mixed $initial = null): mixed
    {
        return array_reduce($this->keyValues, $reducer, $initial);
    }

    public function filterKV(callable $predicate): static
    {
        return new self(array_filter($this->keyValues, $predicate));
    }

    public function mapKV(callable $mapper): static
    {
        return new self(array_map($mapper, $this->keyValues));
    }

    public function reverse(): static
    {
        return new self(array_reverse($this->keyValues, preserve_keys: true));
    }

    public function usortKV(callable $comparator): static
    {
        $keyValues = $this->keyValues;
        uasort($keyValues, $comparator);

        return new self($keyValues);
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new self(\array_slice($this->keyValues, $offset, $length));
    }

    public function keys(): Sequence
    {
        throw new \LogicException('TODO');
    }

    public function values(): Sequence
    {
        throw new \LogicException('TODO');
    }

    public function keyValues(): Sequence
    {
        throw new \LogicException('TODO');
    }

    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * @return \Generator<K, V>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->keyValues as $keyValue) {
            yield $keyValue->key => $keyValue->value;
        }
    }
}
