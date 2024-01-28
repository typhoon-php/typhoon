<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements NameResolver<class-string>
 */
final class NameAsClassResolver implements NameResolver
{
    public function class(string $name): mixed
    {
        return $name;
    }

    public function static(string $self): mixed
    {
        return $self;
    }

    public function constant(string $name): mixed
    {
        throw new ReflectionException(sprintf('Name "%s" cannot be resolved as class.', $name));
    }

    public function classTemplate(string $class, string $name): mixed
    {
        throw new ReflectionException(sprintf('Name "%s" cannot be resolved as class.', $class));
    }

    public function methodTemplate(string $class, string $method, string $name): mixed
    {
        throw new ReflectionException(sprintf('Name "%s" cannot be resolved as class.', $name));
    }

    public function classOrConstants(string $classCandidate, array $constantCandidates): mixed
    {
        /** @var class-string */
        return $classCandidate;
    }
}
