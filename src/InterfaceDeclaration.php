<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 * @template T of object
 */
final class InterfaceDeclaration
{
    /**
     * @param class-string<T> $name
     * @param array<non-empty-string, TemplateDeclaration> $templates
     * @param array<interface-string, list<Type>> $interfacesTemplateArguments
     * @param array<non-empty-string, MethodDeclaration> $methods
     */
    public function __construct(
        public readonly string $name,
        public readonly array $templates,
        public readonly array $interfacesTemplateArguments,
        public readonly array $methods,
    ) {
    }
}
