<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * @api
 * @psalm-suppress MixedAssignment
 */
final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        if (\array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value, null|\DateInterval|int $ttl = null): bool
    {
        $this->validateKey($key);
        $this->items[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $items = [];

        foreach ($keys as $key) {
            $this->validateKey($key);
            $items[$key] = $this->get($key, $default);
        }

        return $items;
    }

    public function setMultiple(iterable $values, null|\DateInterval|int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            \assert(\is_string($key));
            $this->validateKey($key);
            $this->items[$key] = $value;
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
            unset($this->items[$key]);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        return \array_key_exists($key, $this->items);
    }

    private function validateKey(string $key): void
    {
        if (preg_match('#[{}()/\\\@:]#', $key)) {
            throw new InvalidCacheKey($key);
        }
    }
}
