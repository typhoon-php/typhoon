<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\ClassLikeReflection;
use ExtendedTypeSystem\Reflection\MethodReflection;
use ExtendedTypeSystem\Reflection\TemplateReflection;
use ExtendedTypeSystem\Reflection\TypeReflection;

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

    public function addInheritedClassLike(ClassLikeMetadata $metadata): self
    {
        foreach ($metadata->inheritablePropertyTypes() as $name => $propertyType) {
            $this->property($name)->type->addPrototype($propertyType);
        }

        foreach ($metadata->inheritableMethods() as $method) {
            $this->method($method->name)->addPrototype($method);
        }

        return $this;
    }

    /**
     * @return ClassLikeMetadata<T>
     */
    public function build(): ClassLikeMetadata
    {
        return new ClassLikeMetadata(
            reflection: ClassLikeReflection::create(
                name: $this->name,
                templates: $this->templates,
                propertyTypes: array_map(
                    static fn (PropertyReflectionBuilder $builder): TypeReflection => $builder->type->build(),
                    $this->properties,
                ),
                methods: array_map(
                    static fn (MethodReflectionBuilder $builder): MethodReflection => $builder->build(),
                    $this->methods,
                ),
            ),
            inheritablePropertyNames: array_keys(
                array_filter(
                    $this->properties,
                    static fn (PropertyReflectionBuilder $property): bool => $property->inheritable,
                ),
            ),
            inheritableMethodNames: array_keys(
                array_filter(
                    $this->methods,
                    static fn (MethodReflectionBuilder $method): bool => $method->inheritable,
                ),
            ),
        );
    }
}
