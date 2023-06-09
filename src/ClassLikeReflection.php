<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\TypeResolver\ClassTemplateResolver;
use ExtendedTypeSystem\Reflection\TypeResolver\StaticResolver;
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
     * @param class-string<T> $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param array<non-empty-string, TypeReflection> $propertyTypes
     * @param array<non-empty-string, MethodReflection> $methods
     */
    private function __construct(
        public readonly string $name,
        public readonly array $templates,
        public readonly array $propertyTypes,
        public readonly array $methods,
    ) {
    }

    /**
     * @template TObject
     * @param class-string<TObject> $name
     * @param array<TemplateReflection> $templates
     * @param array<non-empty-string, TypeReflection> $propertyTypes
     * @param array<MethodReflection> $methods
     * @return self<TObject>
     */
    public static function create(
        string $name,
        array $templates = [],
        array $propertyTypes = [],
        array $methods = [],
    ): self {
        return new self(
            name: $name,
            templates: array_column($templates, null, 'name'),
            propertyTypes: $propertyTypes,
            methods: array_column($methods, null, 'name'),
        );
    }

    public function template(string $name): TemplateReflection
    {
        return $this->templates[$name] ?? throw new TypeReflectionException(sprintf(
            'Class %s does not have template %s.',
            $this->name,
            $name,
        ));
    }

    public function propertyType(string $name): TypeReflection
    {
        return $this->propertyTypes[$name]
            ?? throw new TypeReflectionException(sprintf('Property %s::$%s does not exist.', $this->name, $name));
    }

    public function method(string $name): MethodReflection
    {
        return $this->methods[$name]
            ?? throw new TypeReflectionException(sprintf('Method %s::%s() does not exist.', $this->name, $name));
    }

    /**
     * @return self<T>
     */
    public function withResolvedStatic(): self
    {
        return $this->withResolvedTypes(new StaticResolver($this->name));
    }

    /**
     * @param array<Type> $templateArguments
     * @return self<T>
     */
    public function withResolvedTemplates(array $templateArguments = []): self
    {
        if ($this->templates === []) {
            return $this;
        }

        $resolvedTemplateArguments = [];

        foreach ($this->templates as $template) {
            $resolvedTemplateArguments[$template->name] = $templateArguments[$template->name]
                ?? $templateArguments[$template->position]
                ?? $template->constraint;
        }

        $typeResolver = new ClassTemplateResolver($this->name, $resolvedTemplateArguments);

        return $this->withResolvedTypes($typeResolver);
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     * @return self<T>
     */
    public function withResolvedTypes(TypeVisitor $typeResolver): self
    {
        return new self(
            name: $this->name,
            templates: $this->templates,
            propertyTypes: array_map(
                static fn (TypeReflection $type): TypeReflection => $type->withResolvedTypes($typeResolver),
                $this->propertyTypes,
            ),
            methods: array_map(
                static fn (MethodReflection $method): MethodReflection => $method->withResolvedTypes($typeResolver),
                $this->methods,
            ),
        );
    }
}
