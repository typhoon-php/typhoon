<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\ClassLikeReflection;
use ExtendedTypeSystem\Reflection\MethodReflection;
use ExtendedTypeSystem\Reflection\PropertyReflection;
use ExtendedTypeSystem\Reflection\TemplateReflection;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection\TypeReflector
 * @template T of object
 */
final class ClassLikeReflectionBuilder
{
    /**
     * @var array<non-empty-string, TemplateReflection>
     */
    private array $templates = [];

    /**
     * @var array<non-empty-string, PropertyReflectionBuilder>
     */
    private array $properties = [];

    /**
     * @var array<non-empty-string, MethodReflectionBuilder>
     */
    private array $methods = [];

    /**
     * @param class-string<T> $name
     */
    public function __construct(
        private readonly string $name,
    ) {
    }

    /**
     * @param array<non-empty-string, TemplateReflection> $templates
     */
    public function templates(array $templates): self
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * @param non-empty-string $name
     */
    public function property(string $name): PropertyReflectionBuilder
    {
        return $this->properties[$name] ??= new PropertyReflectionBuilder();
    }

    /**
     * @param non-empty-string $name
     */
    public function method(string $name): MethodReflectionBuilder
    {
        return $this->methods[$name] ??= new MethodReflectionBuilder($this->name, $name);
    }

    public function addInheritedClassLike(ClassLikeReflection $classLike): self
    {
        /** @psalm-suppress PossiblyNullFunctionCall, InaccessibleProperty */
        \Closure::bind(function () use ($classLike): void {
            foreach ($classLike->properties as $name => $property) {
                if (!$property->private) {
                    $this->property($name)->addPrototype($property);
                }
            }

            \Closure::bind(function () use ($classLike): void {
                foreach ($classLike->methods() as $name => $method) {
                    if (!$method->private) {
                        $this->method($name)->addPrototype($method);
                    }
                }
            }, $this, MethodReflection::class)();
        }, $this, $classLike)();

        return $this;
    }

    /**
     * @return ClassLikeReflection<T>
     */
    public function build(): ClassLikeReflection
    {
        return new ClassLikeReflection(
            name: $this->name,
            templates: $this->templates,
            properties: array_map(
                static fn (PropertyReflectionBuilder $builder): PropertyReflection => $builder->build(),
                $this->properties,
            ),
            methods: array_map(
                static fn (MethodReflectionBuilder $builder): MethodReflection => $builder->build(),
                $this->methods,
            ),
        );
    }
}
