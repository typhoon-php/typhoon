<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class MethodDeclaration
{
    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, TemplateDeclaration> $templatesByName
     * @param array<non-empty-string, TypeDeclaration> $parameterTypesByName
     */
    public function __construct(
        public readonly string $name,
        public readonly array $templatesByName,
        public readonly array $parameterTypesByName,
        public readonly ?TypeDeclaration $returnType,
    ) {
    }
}
