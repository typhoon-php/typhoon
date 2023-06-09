<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\ClassLikeReflection;
use ExtendedTypeSystem\Reflection\MethodReflection;
use ExtendedTypeSystem\Reflection\TypeReflection;
use ExtendedTypeSystem\Type;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection\TypeReflector
 * @psalm-immutable
 * @template T of object
 */
final class ClassLikeMetadata
{
    /**
     * @param ClassLikeReflection<T> $reflection
     * @param list<non-empty-string> $inheritablePropertyNames
     * @param list<non-empty-string> $inheritableMethodNames
     */
    public function __construct(
        public readonly ClassLikeReflection $reflection,
        private readonly array $inheritablePropertyNames,
        private readonly array $inheritableMethodNames,
    ) {
    }

    /**
     * @return \Generator<non-empty-string, TypeReflection>
     */
    public function inheritablePropertyTypes(): \Generator
    {
        foreach ($this->inheritablePropertyNames as $name) {
            yield $name => $this->reflection->propertyType($name);
        }
    }

    /**
     * @return \Generator<non-empty-string, MethodReflection>
     */
    public function inheritableMethods(): \Generator
    {
        foreach ($this->inheritableMethodNames as $name) {
            yield $name => $this->reflection->method($name);
        }
    }

    /**
     * @param array<Type> $templateArguments
     * @return self<T>
     */
    public function withResolvedTemplates(array $templateArguments = []): self
    {
        return new self(
            reflection: $this->reflection->withResolvedTemplates($templateArguments),
            inheritablePropertyNames: $this->inheritablePropertyNames,
            inheritableMethodNames: $this->inheritableMethodNames,
        );
    }
}
