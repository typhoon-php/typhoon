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
final class NullReflectionCache implements ReflectionCache
{
    public function getReflection(string $class, string $name): ?RootReflection
    {
        return null;
    }

    public function addReflection(RootReflection $reflection): void {}

    public function deleteReflection(string $class, string $name): void {}

    public function getResource(string $file): ?Resource
    {
        return null;
    }

    public function addResource(Resource $resource): void {}

    public function deleteResource(string $file): void {}
}
