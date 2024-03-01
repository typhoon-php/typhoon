<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * @api
 */
final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function __construct(
        private readonly int $capacity = 1000,
    ) {}

    private static function validateKey(string $key): void
    {
        if (preg_match('#[{}()/\\\@:]#', $key)) {
            throw new InvalidCacheKey($key);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        self::validateKey($key);

        if (!\array_key_exists($key, $this->values)) {
            return $default;
        }

        $value = $this->values[$key];
        unset($this->values[$key]);

        return $this->values[$key] = $value;
    }

    public function set(string $key, mixed $value, null|\DateInterval|int $ttl = null): bool
    {
        self::validateKey($key);

        unset($this->values[$key]);
        $this->values[$key] = $value;
        $this->evict();

        return true;
    }

    public function delete(string $key): bool
    {
        self::validateKey($key);

        unset($this->values[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|\DateInterval|int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            \assert(\is_string($key));
            self::validateKey($key);

            unset($this->values[$key]);
            $this->values[$key] = $value;
        }

        $this->evict();

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    private function evict(): void
    {
        if (\count($this->values) > $this->capacity) {
            $this->values = \array_slice($this->values, -$this->capacity);
        }
    }
}
