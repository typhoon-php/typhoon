<?php

declare(strict_types=1);

namespace Typhoon\TypedMap;

/**
 * @api
 * @psalm-immutable
 * @implements \ArrayAccess<Key, mixed>
 */
final class TypedMap implements \ArrayAccess, \Countable
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
        return $key::class . '::' . $key->name;
    }

    /**
     * @psalm-immutable
     * @template T
     * @param Key<T> $key
     * @param T $value
     */
    public static function one(Key $key, mixed $value): self
    {
        $map = new self();
        $map->values[self::keyToString($key)] = $value;

        return $map;
    }

    /**
     * @template T
     * @param Key<T> $key
     * @param T $value
     */
    public function with(Key $key, mixed $value): self
    {
        $stringKey = self::keyToString($key);

        if ($key instanceof OptionalKey && $value === $key->default($this)) {
            if (isset($this->values[$stringKey])) {
                $copy = clone $this;
                unset($copy->values[$stringKey]);

                return $copy;
            }

            return $this;
        }

        $copy = clone $this;
        $copy->values[$stringKey] = $value;

        return $copy;
    }

    public function withMap(self $map): self
    {
        $copy = clone $map;
        $copy->values += $this->values;

        return $copy;
    }

    public function without(Key ...$keys): self
    {
        $copy = clone $this;

        foreach ($keys as $key) {
            unset($copy->values[self::keyToString($key)]);
        }

        return $copy;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[self::keyToString($offset)]);
    }

    /**
     * @template T
     * @param Key<T> $offset
     * @return T
     * @throws KeyIsNotDefined
     */
    public function offsetGet(mixed $offset): mixed
    {
        $key = self::keyToString($offset);

        if (isset($this->values[$key])) {
            /** @var T */
            return $this->values[$key];
        }

        if ($offset instanceof OptionalKey) {
            /** @var OptionalKey<T> $offset */
            return $offset->default($this);
        }

        throw new KeyIsNotDefined($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \BadMethodCallException(\sprintf('%s is immutable', self::class));
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \BadMethodCallException(\sprintf('%s is immutable', self::class));
    }

    public function __serialize(): array
    {
        return $this->values;
    }

    /**
     * @param array<non-empty-string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->values = $data;
    }

    public function count(): int
    {
        return \count($this->values);
    }
}
