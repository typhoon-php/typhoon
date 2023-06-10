<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 * @implements NameResolver<non-empty-string>
 */
final class ClassNameResolver implements NameResolver
{
    public function class(string $name): mixed
    {
        return $name;
    }

    public function static(string $class): mixed
    {
        throw new \LogicException();
    }

    public function constant(string $name): mixed
    {
        throw new \LogicException();
    }

    public function classTemplate(string $class, string $name): mixed
    {
        throw new \LogicException();
    }

    public function methodTemplate(string $class, string $method, string $name): mixed
    {
        throw new \LogicException();
    }

    public function classOrConstants(string $class, array $constants): mixed
    {
        return $class;
    }
}
