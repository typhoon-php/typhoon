<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 */
final class MethodReflection
{
    /**
     * @param class-string $reflectedClass
     * @param non-empty-string $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param array<non-empty-string, TypeReflection> $parameterTypes
     */
    private function __construct(
        public readonly string $reflectedClass,
        public readonly string $name,
        public readonly array $templates,
        public readonly array $parameterTypes,
        public readonly TypeReflection $returnType,
    ) {
    }

    /**
     * @param class-string $reflectedClass
     * @param non-empty-string $name
     * @param array<TemplateReflection> $templates
     * @param array<non-empty-string, TypeReflection> $parameterTypes
     */
    public static function create(
        string $reflectedClass,
        string $name,
        array $templates = [],
        array $parameterTypes = [],
        TypeReflection $returnType = new TypeReflection(),
    ): self {
        return new self(
            reflectedClass: $reflectedClass,
            name: $name,
            templates: array_column($templates, null, 'name'),
            parameterTypes: $parameterTypes,
            returnType: $returnType,
        );
    }

    public function template(string $name): TemplateReflection
    {
        return $this->templates[$name] ?? throw new TypeReflectionException(
            sprintf(
                'Method %s::%s() does not have template %s.',
                $this->reflectedClass,
                $this->name,
                $name,
            ),
        );
    }

    public function parameterType(string $name): TypeReflection
    {
        return $this->parameterTypes[$name] ?? throw new TypeReflectionException(
            sprintf(
                'Method %s::%s() does not have parameter $%s.',
                $this->reflectedClass,
                $this->name,
                $name,
            ),
        );
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function withResolvedTypes(TypeVisitor $typeResolver): self
    {
        return new self(
            reflectedClass: $this->reflectedClass,
            name: $this->name,
            templates: $this->templates,
            parameterTypes: array_map(
                static fn (TypeReflection $type): TypeReflection => $type->withResolvedTypes($typeResolver),
                $this->parameterTypes,
            ),
            returnType: $this->returnType->withResolvedTypes($typeResolver),
        );
    }
}
