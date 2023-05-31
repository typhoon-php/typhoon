<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template T of object
 */
final class ClassLikeReflection
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem\Reflection
     * @param class-string<T> $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param array<non-empty-string, PropertyReflection> $properties
     * @param array<non-empty-string, MethodReflection> $methods
     */
    public function __construct(
        public readonly string $name,
        private readonly array $templates,
        private readonly array $properties,
        private readonly array $methods,
    ) {
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    public function templates(): array
    {
        return $this->templates;
    }

    /**
     * @throws TypeReflectionException
     */
    public function template(string $name): TemplateReflection
    {
        return $this->templates[$name] ?? throw new TypeReflectionException(sprintf(
            'Template %s is either resolved or not declared in class %s.',
            $name,
            $this->name,
        ));
    }

    /**
     * @throws TypeReflectionException
     */
    public function templateByIndex(int $index): TemplateReflection
    {
        return array_values($this->templates)[$index] ?? throw new TypeReflectionException(sprintf(
            'Template with index %d is either resolved or not declared in class %s.',
            $index,
            $this->name,
        ));
    }

    /**
     * @return array<non-empty-string, TypeReflection>
     */
    public function propertyTypes(): array
    {
        return array_map(
            static fn (PropertyReflection $property): TypeReflection => $property->type,
            $this->properties,
        );
    }

    /**
     * @throws TypeReflectionException
     */
    public function propertyType(string $name): TypeReflection
    {
        $property = $this->properties[$name]
            ?? throw new TypeReflectionException(sprintf('Property %s::$%s does not exist.', $this->name, $name));

        return $property->type;
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    public function method(string $name): MethodReflection
    {
        return $this->methods[$name]
            ?? throw new TypeReflectionException(sprintf('Method %s::%s() does not exist.', $this->name, $name));
    }

    /**
     * @return self<T>
     */
    public function resolveStatic(): self
    {
        return $this->resolveTypes(new StaticResolver($this->name));
    }

    /**
     * @param array<Type> $templateArguments
     * @return self<T>
     */
    public function resolveTemplates(array $templateArguments = []): self
    {
        if ($this->templates === []) {
            return $this;
        }

        $resolvedTemplateArguments = [];

        foreach ($this->templates as $template) {
            $resolvedTemplateArguments[$template->name] = $templateArguments[$template->name]
                ?? $templateArguments[$template->index]
                ?? $template->constraint;
        }

        $typeResolver = new ClassTemplateResolver($this->name, $resolvedTemplateArguments);

        return $this->resolveTypes($typeResolver);
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     * @return self<T>
     */
    public function resolveTypes(TypeVisitor $typeResolver): self
    {
        return new self(
            name: $this->name,
            templates: $this->templates,
            properties: array_map(
                static fn (PropertyReflection $property): PropertyReflection => $property->resolveTypes($typeResolver),
                $this->properties,
            ),
            methods: array_map(
                static fn (MethodReflection $method): MethodReflection => $method->resolveTypes($typeResolver),
                $this->methods,
            ),
        );
    }
}
