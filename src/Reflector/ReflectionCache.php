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
     * @param non-empty-string $file
     */
    public function hasFile(string $file): bool;

    /**
     * @param class-string<RootReflection> $reflectionClass
     * @param non-empty-string $name
     */
    public function hasReflection(string $reflectionClass, string $name): bool;

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $reflectionClass
     * @param non-empty-string $name
     * @return ?TReflection
     */
    public function getReflection(string $reflectionClass, string $name): ?RootReflection;

    public function setStandaloneReflection(RootReflection $reflection): void;

    /**
     * @param non-empty-string $file
     */
    public function setFileReflections(string $file, Reflections $reflections): void;

    public function clear(): void;
}
