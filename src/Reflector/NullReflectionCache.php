<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

final class NullReflectionCache implements ReflectionCache
{
    public function hasFile(string $file): bool
    {
        return false;
    }

    public function hasReflection(string $reflectionClass, string $name): bool
    {
        return false;
    }

    public function getReflection(string $reflectionClass, string $name): ?RootReflection
    {
        return null;
    }

    public function setStandaloneReflection(RootReflection $reflection): void {}

    public function setFileReflections(string $file, Reflections $reflections): void {}

    public function clear(): void {}
}
