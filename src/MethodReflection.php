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
     * @internal
     * @psalm-internal ExtendedTypeSystem\Reflection
     * @param class-string $reflectedClass
     * @param non-empty-string $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param array<non-empty-string, TypeReflection> $parameterTypes
     */
    public function __construct(
        public readonly string $reflectedClass,
        public readonly string $name,
        private readonly bool $private,
        private readonly array $templates,
        private readonly array $parameterTypes,
        private readonly TypeReflection $returnType,
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
            'Template %s is either resolved or not declared in method %s::%s().',
            $name,
            $this->reflectedClass,
            $this->name,
        ));
    }

    /**
     * @throws TypeReflectionException
     */
    public function templateByPosition(int $position): TemplateReflection
    {
        return array_values($this->templates)[$position] ?? throw new TypeReflectionException(sprintf(
            'Template at position %d is either resolved or not declared in method %s::%s().',
            $position,
            $this->reflectedClass,
            $this->name,
        ));
    }

    /**
     * @return array<non-empty-string, TypeReflection>
     */
    public function parameterTypes(): array
    {
        return $this->parameterTypes;
    }

    /**
     * @throws TypeReflectionException
     */
    public function parameterType(string $name): TypeReflection
    {
        return $this->parameterTypes[$name] ?? throw new TypeReflectionException(sprintf(
            'Method %s::%s() does not have parameter %s.',
            $this->reflectedClass,
            $this->name,
            $name,
        ));
    }

    /**
     * @throws TypeReflectionException
     */
    public function parameterTypeByPosition(int $position): TypeReflection
    {
        return array_values($this->parameterTypes)[$position] ?? throw new TypeReflectionException(sprintf(
            'Method %s::%s() does not have parameter at position %d.',
            $this->reflectedClass,
            $this->name,
            $position,
        ));
    }

    public function returnType(): TypeReflection
    {
        return $this->returnType;
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function resolveTypes(TypeVisitor $typeResolver): self
    {
        return new self(
            reflectedClass: $this->reflectedClass,
            name: $this->name,
            private: $this->private,
            templates: $this->templates,
            parameterTypes: array_map(
                static fn (TypeReflection $type): TypeReflection => $type->resolveTypes($typeResolver),
                $this->parameterTypes,
            ),
            returnType: $this->returnType->resolveTypes($typeResolver),
        );
    }
}
