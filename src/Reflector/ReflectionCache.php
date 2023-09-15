<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ReflectionCache
{
    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @return ?RootReflection
     */
    public function getReflection(string $class, string $name): ?RootReflection;

    public function addReflection(RootReflection $reflection): void;

    /**
     * @param class-string<RootReflection> $class
     * @param non-empty-string $name
     */
    public function deleteReflection(string $class, string $name): void;

    /**
     * @param non-empty-string $file
     */
    public function getResource(string $file): ?Resource;

    public function addResource(Resource $resource): void;

    /**
     * @param non-empty-string $file
     */
    public function deleteResource(string $file): void;
}
