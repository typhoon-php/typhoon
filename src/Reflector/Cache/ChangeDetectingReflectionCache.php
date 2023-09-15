<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector\Cache;

use Typhoon\Reflection\Reflector\ReflectionCache;
use Typhoon\Reflection\Reflector\Resource;
use Typhoon\Reflection\Reflector\RootReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ChangeDetectingReflectionCache implements ReflectionCache
{
    public function __construct(
        private readonly ReflectionCache $cache,
    ) {}

    public function getReflection(string $class, string $name): ?RootReflection
    {
        $reflection = $this->cache->getReflection($class, $name);

        if ($reflection?->getChangeDetector()->changed()) {
            $this->cache->deleteReflection($class, $name);

            return null;
        }

        return $reflection;
    }

    public function addReflection(RootReflection $reflection): void
    {
        $this->cache->addReflection($reflection);
    }

    public function deleteReflection(string $class, string $name): void
    {
        $this->cache->deleteReflection($class, $name);
    }

    public function getResource(string $file): ?Resource
    {
        $resource = $this->cache->getResource($file);

        if ($resource?->changeDetector->changed()) {
            $this->cache->deleteResource($file);

            return null;
        }

        return $resource;
    }

    public function addResource(Resource $resource): void
    {
        $this->cache->addResource($resource);
    }

    public function deleteResource(string $file): void
    {
        $this->cache->deleteResource($file);
    }
}
