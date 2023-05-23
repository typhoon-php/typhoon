<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 * @template T of object
 */
final class TraitDeclaration
{
    /**
     * @param class-string<T> $name
     * @param array<non-empty-string, TemplateDeclaration> $templatesByName
     * @param array<trait-string, list<Type>> $usedTraitsByName
     * @param array<non-empty-string, TypeDeclaration> $propertyTypesByName
     * @param array<non-empty-string, MethodDeclaration> $methodsByName
     */
    public function __construct(
        public readonly string $name,
        public readonly array $templatesByName,
        public readonly array $usedTraitsByName,
        public readonly array $propertyTypesByName,
        public readonly array $methodsByName,
    ) {
    }
}
