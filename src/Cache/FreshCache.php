<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Cache;

use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\Metadata\MetadataCacheItem;

/**
 * @api
 * @psalm-suppress MixedAssignment
 */
final class FreshCache implements CacheInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->cache->get($key, $default);

        if ($value instanceof MetadataCacheItem && $value->changed()) {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value, null|\DateInterval|int $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * @param iterable<string> $keys
     * @return array<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($this->cache->getMultiple($keys) as $key => $value) {
            if ($value instanceof MetadataCacheItem && $value->changed()) {
                $value = $default;
            }

            $values[$key] = $value;
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|\DateInterval|int $ttl = null): bool
    {
        return $this->cache->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->cache->deleteMultiple($keys);
    }

    public function has(string $key): bool
    {
        $value = $this->cache->get($key);

        if ($value instanceof MetadataCacheItem && $value->changed()) {
            return false;
        }

        return true;
    }
}
