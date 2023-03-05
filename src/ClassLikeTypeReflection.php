<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @template T of object
 */
final class ClassLikeTypeReflection
{
    /**
     * @param class-string<T> $name
     * @param ?class-string $parentClass
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param ?list<Type> $parentTemplateArguments
     * @param array<class-string, list<Type>> $interfacesTemplateArguments
     * @param array<class-string, list<Type>> $traitsTemplateArguments
     * @param array<non-empty-string, Type> $propertyTypes
     * @param array<non-empty-string, MethodTypeReflection> $methods
     */
    public function __construct(
        private readonly TypeReflector $typeReflector,
        public readonly string $name,
        private readonly ?string $parentClass,
        public readonly array $templates,
        private readonly ?array $parentTemplateArguments,
        private readonly array $interfacesTemplateArguments,
        private readonly array $traitsTemplateArguments,
        private readonly array $propertyTypes,
        private readonly array $methods,
    ) {
    }

    /**
     * @return list<Type>
     */
    public function parentTemplateArguments(): array
    {
        return $this->parentTemplateArguments ?? throw new \LogicException(sprintf(
            'Class %s does not have a parent.',
            $this->name,
        ));
    }

    /**
     * @param class-string $name
     * @return list<Type>
     */
    public function interfaceTemplateArguments(string $name): array
    {
        return $this->interfacesTemplateArguments[$name] ?? throw new \LogicException(sprintf(
            'Class %s does not directly implement %s.',
            $this->name,
            $name,
        ));
    }

    /**
     * @param class-string $name
     * @return list<Type>
     */
    public function traitTemplateArguments(string $name): array
    {
        return $this->traitsTemplateArguments[$name] ?? throw new \LogicException(sprintf(
            'Class %s does not directly use %s.',
            $this->name,
            $name,
        ));
    }

    /**
     * @param non-empty-string $name
     */
    public function propertyType(string $name): Type
    {
        return $this->tryPropertyType($name) ?? throw new \LogicException(sprintf(
            'Class %s does not have property %s.',
            $this->name,
            $name,
        ));
    }

    /**
     * @param non-empty-string $name
     */
    public function method(string $name): MethodTypeReflection
    {
        return $this->tryMethod($name) ?? throw new \LogicException(sprintf(
            'Class %s does not have method %s.',
            $this->name,
            $name,
        ));
    }

    /**
     * @param non-empty-string $name
     */
    private function tryPropertyType(string $name): ?Type
    {
        if (isset($this->propertyTypes[$name])) {
            return $this->propertyTypes[$name];
        }

        foreach ($this->classParts() as $class) {
            $type = $class->tryPropertyType($name);

            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param non-empty-string $name
     */
    private function tryMethod(string $name): ?MethodTypeReflection
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }

        foreach ($this->classParts() as $class) {
            $method = $class->tryMethod($name);

            if ($method !== null) {
                return $method;
            }
        }

        return null;
    }

    /**
     * @return \Generator<int, self>
     */
    private function classParts(): \Generator
    {
        foreach ($this->traitsTemplateArguments as $traitClass => $_) {
            yield $this->typeReflector->reflectClass($traitClass);
        }

        if ($this->parentClass !== null) {
            yield $this->typeReflector->reflectClass($this->parentClass);
        }

        foreach ($this->interfacesTemplateArguments as $interfaceClass => $_) {
            yield $this->typeReflector->reflectClass($interfaceClass);
        }
    }
}
