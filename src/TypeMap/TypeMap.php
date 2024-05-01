<?php

declare(strict_types=1);

namespace Typhoon\TypeMap;

/**
 * @api
 * @psalm-immutable
 * @implements \ArrayAccess<Key, mixed>
 * @implements \IteratorAggregate<Key, mixed>
 */
final class TypeMap implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array<non-empty-string, mixed>
     */
    private array $values = [];

    /**
     * @psalm-pure
     * @return non-empty-string
     */
    private static function keyToString(Key $key): string
    {
        return sprintf('%s::%s', $key::class, $key->name);
    }

    public function has(Key $key): bool
    {
        return \array_key_exists(self::keyToString($key), $this->values);
    }

    /**
     * @template T
     * @template TDefault
     * @param Key<T> $key
     * @param TDefault $default
     * @return T|TDefault
     */
    public function get(Key $key, mixed $default = null): mixed
    {
        $stringKey = self::keyToString($key);

        if (\array_key_exists($stringKey, $this->values)) {
            /** @var T */
            return $this->values[$stringKey];
        }

        return $default;
    }

    /**
     * @template T
     * @param Key<T> $key
     * @return T
     */
    public function require(Key $key): mixed
    {
        $stringKey = self::keyToString($key);

        if (\array_key_exists($stringKey, $this->values)) {
            /** @var T */
            return $this->values[$stringKey];
        }

        throw new UndefinedKey($key);
    }

    /**
     * @template T
     * @param Key<T> $key
     * @param T $value
     */
    public function with(Key $key, mixed $value): self
    {
        $values = clone $this;
        $values->values[self::keyToString($key)] = $value;

        return $values;
    }

    public function without(Key ...$keys): self
    {
        $values = clone $this;

        foreach ($keys as $key) {
            unset($values->values[self::keyToString($key)]);
        }

        return $values;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->require($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException(sprintf('%s is immutable', self::class));
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException(sprintf('%s is immutable', self::class));
    }

    /**
     * @return \Generator<Key, mixed>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->values as $key => $value) {
            $key = \constant($key);
            \assert($key instanceof Key);

            yield $key => $value;
        }
    }

    public function count(): int
    {
        return \count($this->values);
    }
}
