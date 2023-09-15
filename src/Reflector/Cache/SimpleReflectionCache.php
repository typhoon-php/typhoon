<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector\Cache;

use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\Reflector\ReflectionCache;
use Typhoon\Reflection\Reflector\Resource;
use Typhoon\Reflection\Reflector\RootReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class SimpleReflectionCache implements ReflectionCache
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @return ?TReflection
     */
    public function getReflection(string $class, string $name): ?RootReflection
    {
        /** @var ?TReflection */
        return $this->cache->get($this->reflectionKey($class, $name));
    }

    public function addReflection(RootReflection $reflection): void
    {
        $this->cache->set($this->reflectionKey($reflection::class, $reflection->getName()), $reflection);
    }

    public function deleteReflection(string $class, string $name): void
    {
        $this->cache->delete($this->reflectionKey($class, $name));
    }

    public function getResource(string $file): ?Resource
    {
        /** @var ?Resource */
        return $this->cache->get($this->resourceKey($file));
    }

    public function addResource(Resource $resource): void
    {
        $this->cache->set($this->resourceKey($resource->file), $resource);
    }

    public function deleteResource(string $file): void
    {
        $this->cache->delete($this->resourceKey($file));
    }

    /**
     * @param class-string<RootReflection> $class
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function reflectionKey(string $class, string $name): string
    {
        return hash('xxh128', $class . '#' . $name);
    }

    /**
     * @param non-empty-string $file
     * @return non-empty-string
     */
    private function resourceKey(string $file): string
    {
        return hash('xxh128', $file);
    }
}
