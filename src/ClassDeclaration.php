<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 * @template T of object
 */
final class ClassDeclaration
{
    /**
     * @param class-string<T> $name
     * @param array<non-empty-string, TemplateDeclaration> $templates
     * @param ?class-string $parent
     * @param list<Type> $parentTemplateArguments
     * @param array<interface-string, list<Type>> $interfacesTemplateArguments
     * @param array<trait-string, list<Type>> $traitsTemplateArguments
     * @param array<non-empty-string, TypeDeclaration> $constantTypes
     * @param array<non-empty-string, TypeDeclaration> $propertyTypes
     * @param array<non-empty-string, MethodDeclaration> $methods
     */
    public function __construct(
        public readonly string $name,
        public readonly array $templates,
        public readonly ?string $parent,
        public readonly array $parentTemplateArguments,
        public readonly array $interfacesTemplateArguments,
        public readonly array $traitsTemplateArguments,
        public readonly array $constantTypes,
        public readonly array $propertyTypes,
        public readonly array $methods,
    ) {
    }
}
