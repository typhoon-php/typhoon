<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TTemplateMetadata
 * @implements NameResolution<class-string, TTemplateMetadata>
 */
final class NameAsClassResolution implements NameResolution
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
        throw new ReflectionException(sprintf('Constant name "%s" cannot be resolved as class.', $name));
    }

    public function classTemplate(string $class, string $name, mixed $metadata): mixed
    {
        throw new ReflectionException(sprintf('Class template name "%s" cannot be resolved as class.', $name));
    }

    public function methodTemplate(string $class, string $method, string $name, mixed $metadata): mixed
    {
        throw new ReflectionException(sprintf('Method template name "%s" cannot be resolved as class.', $name));
    }

    public function classOrConstants(string $classCandidate, array $constantCandidates): mixed
    {
        /** @var class-string */
        return $classCandidate;
    }
}
