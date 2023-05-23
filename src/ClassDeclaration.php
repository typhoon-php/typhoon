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
     * @param array<non-empty-string, TemplateDeclaration> $templatesByName
     * @param ?class-string $parentClass
     * @param list<Type> $parentClassTemplateArguments
     * @param array<interface-string, list<Type>> $implementedInterfacesByName
     * @param array<trait-string, list<Type>> $usedTraitsByName
     * @param array<non-empty-string, TypeDeclaration> $constantTypesByName
     * @param array<non-empty-string, TypeDeclaration> $propertyTypesByName
     * @param array<non-empty-string, MethodDeclaration> $methodsByName
     */
    public function __construct(
        public readonly string $name,
        public readonly array $templatesByName,
        public readonly ?string $parentClass,
        public readonly array $parentClassTemplateArguments,
        public readonly array $implementedInterfacesByName,
        public readonly array $usedTraitsByName,
        public readonly array $constantTypesByName,
        public readonly array $propertyTypesByName,
        public readonly array $methodsByName,
    ) {
    }
}
