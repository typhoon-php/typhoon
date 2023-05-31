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
     * @param array<non-empty-string, TypeReflection> $nonInheritablePropertyTypes
     * @param array<non-empty-string, TypeReflection> $inheritablePropertyTypes
     * @param array<non-empty-string, MethodReflection> $nonInheritableMethods
     * @param array<non-empty-string, MethodReflection> $inheritableMethods
     */
    public function __construct(
        public readonly string $name,
        private readonly array $templates,
        private readonly array $nonInheritablePropertyTypes,
        private readonly array $inheritablePropertyTypes,
        private readonly array $nonInheritableMethods,
        private readonly array $inheritableMethods,
    ) {
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    public function templates(): array
    {
        return $this->templates;
    }

    public function template(int|string $name): TemplateReflection
    {
        if (\is_string($name)) {
            return $this->templates[$name] ?? throw new \RuntimeException();
        }

        foreach ($this->templates as $template) {
            if ($template->index === $name) {
                return $template;
            }
        }

        throw new \RuntimeException();
    }

    /**
     * @return array<non-empty-string, TypeReflection>
     */
    public function propertyTypes(): array
    {
        return [...$this->nonInheritablePropertyTypes, ...$this->inheritablePropertyTypes];
    }

    /**
     * @return array<non-empty-string, TypeReflection>
     */
    public function inheritablePropertyTypes(): array
    {
        return $this->inheritablePropertyTypes;
    }

    public function propertyType(string $name): TypeReflection
    {
        return $this->nonInheritablePropertyTypes[$name]
            ?? $this->inheritablePropertyTypes[$name]
            ?? throw new \RuntimeException();
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    public function methods(): array
    {
        return [...$this->nonInheritableMethods, ...$this->inheritableMethods];
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    public function inheritableMethods(): array
    {
        return $this->inheritableMethods;
    }

    public function method(string $name): MethodReflection
    {
        return $this->nonInheritableMethods[$name]
            ?? $this->inheritableMethods[$name]
            ?? throw new \RuntimeException();
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
            nonInheritablePropertyTypes: array_map(
                static fn (TypeReflection $type): TypeReflection => $type->resolve($typeResolver),
                $this->nonInheritablePropertyTypes,
            ),
            inheritablePropertyTypes: array_map(
                static fn (TypeReflection $type): TypeReflection => $type->resolve($typeResolver),
                $this->inheritablePropertyTypes,
            ),
            nonInheritableMethods: array_map(
                static fn (MethodReflection $method): MethodReflection => $method->resolveTypes($typeResolver),
                $this->nonInheritableMethods,
            ),
            inheritableMethods: array_map(
                static fn (MethodReflection $method): MethodReflection => $method->resolveTypes($typeResolver),
                $this->inheritableMethods,
            ),
        );
    }
}
