<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\MethodReflection;
use ExtendedTypeSystem\TemplateReflection;
use ExtendedTypeSystem\TypeReflection;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class MethodReflectionBuilder
{
    public readonly TypeReflectionBuilder $returnType;
    public bool $inheritable = true;

    /**
     * @var array<non-empty-string, TemplateReflection>
     */
    private array $templates = [];

    /**
     * @var array<non-empty-string, TypeReflectionBuilder>
     */
    private array $parameterTypes = [];

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
    ) {
        $this->returnType = new TypeReflectionBuilder();
    }

    public function inheritable(bool $inheritable): self
    {
        $this->inheritable = $inheritable;

        return $this;
    }

    /**
     * @param array<non-empty-string, TemplateReflection> $templates
     */
    public function templates(array $templates): self
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * @param non-empty-string $name
     */
    public function parameterType(string $name): TypeReflectionBuilder
    {
        return $this->parameterTypes[$name] ??= new TypeReflectionBuilder();
    }

    public function addPrototype(MethodReflection $method): self
    {
        $this->returnType->addPrototype($method->returnType());

        foreach ($method->parameterTypes() as $parameterName => $parameterType) {
            ($this->parameterTypes[$parameterName] ??= new TypeReflectionBuilder())->addPrototype($parameterType);
        }

        return $this;
    }

    public function build(): MethodReflection
    {
        return new MethodReflection(
            name: $this->name,
            templates: $this->templates,
            parameterTypes: array_map(
                static fn (TypeReflectionBuilder $builder): TypeReflection => $builder->build(),
                $this->parameterTypes,
            ),
            returnType: $this->returnType->build(),
        );
    }
}
