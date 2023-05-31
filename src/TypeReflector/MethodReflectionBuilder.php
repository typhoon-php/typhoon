<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\MethodReflection;
use ExtendedTypeSystem\Reflection\TemplateReflection;
use ExtendedTypeSystem\Reflection\TypeReflection;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection\TypeReflector
 */
final class MethodReflectionBuilder
{
    public readonly TypeReflectionBuilder $returnType;

    private bool $private = false;

    /**
     * @var array<non-empty-string, TemplateReflection>
     */
    private array $templates = [];

    /**
     * @var array<non-empty-string, TypeReflectionBuilder>
     */
    private array $parameterTypes = [];

    /**
     * @param class-string $reflectedClass
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $reflectedClass,
        private readonly string $name,
    ) {
        $this->returnType = new TypeReflectionBuilder();
    }

    public function private(bool $private): self
    {
        $this->private = $private;

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
            reflectedClass: $this->reflectedClass,
            name: $this->name,
            private: $this->private,
            templates: $this->templates,
            parameterTypes: array_map(
                static fn (TypeReflectionBuilder $builder): TypeReflection => $builder->build(),
                $this->parameterTypes,
            ),
            returnType: $this->returnType->build(),
        );
    }
}
