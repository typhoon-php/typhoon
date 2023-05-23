<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 * @template T of object
 */
final class InterfaceDeclaration
{
    /**
     * @param class-string<T> $name
     * @param array<non-empty-string, TemplateDeclaration> $templatesByName
     * @param array<interface-string, list<Type>> $extendedInterfacesByName
     * @param array<non-empty-string, TypeDeclaration> $constantTypesByName
     * @param array<non-empty-string, MethodDeclaration> $methodsByName
     */
    public function __construct(
        public readonly string $name,
        public readonly array $templatesByName,
        public readonly array $extendedInterfacesByName,
        public readonly array $constantTypesByName,
        public readonly array $methodsByName,
    ) {
    }
}
