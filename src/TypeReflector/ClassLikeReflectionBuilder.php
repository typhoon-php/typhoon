<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\ClassLikeReflection;
use ExtendedTypeSystem\Reflection\MethodReflection;
use ExtendedTypeSystem\Reflection\TemplateReflection;
use ExtendedTypeSystem\Reflection\TypeReflection;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
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
        return $this->methods[$name] ??= new MethodReflectionBuilder($name);
    }

    public function addInheritedClassLike(ClassLikeReflection $classLike): self
    {
        foreach ($classLike->inheritablePropertyTypes() as $name => $property) {
            $this->property($name)->type->addPrototype($property);
        }

        foreach ($classLike->inheritableMethods() as $name => $method) {
            $this->method($name)->addPrototype($method);
        }

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
            nonInheritablePropertyTypes: array_map(
                static fn (PropertyReflectionBuilder $builder): TypeReflection => $builder->type->build(),
                array_filter(
                    $this->properties,
                    static fn (PropertyReflectionBuilder $builder): bool => !$builder->inheritable,
                ),
            ),
            inheritablePropertyTypes: array_map(
                static fn (PropertyReflectionBuilder $builder): TypeReflection => $builder->type->build(),
                array_filter(
                    $this->properties,
                    static fn (PropertyReflectionBuilder $builder): bool => $builder->inheritable,
                ),
            ),
            nonInheritableMethods: array_map(
                static fn (MethodReflectionBuilder $builder): MethodReflection => $builder->build(),
                array_filter(
                    $this->methods,
                    static fn (MethodReflectionBuilder $builder): bool => $builder->inheritable,
                ),
            ),
            inheritableMethods: array_map(
                static fn (MethodReflectionBuilder $builder): MethodReflection => $builder->build(),
                array_filter(
                    $this->methods,
                    static fn (MethodReflectionBuilder $builder): bool => $builder->inheritable,
                ),
            ),
        );
    }
}
