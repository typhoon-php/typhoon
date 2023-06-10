<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\TypeResolver\ClassTemplateResolver;
use ExtendedTypeSystem\Reflection\TypeResolver\StaticResolver;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @template T of object
 */
final class ClassReflection extends Reflection
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     * @var array<non-empty-string, PropertyReflection>
     */
    public readonly array $properties;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     * @var array<non-empty-string, MethodReflection>
     */
    public readonly array $methods;

    /**
     * @param class-string<T> $name
     * @param array<non-empty-string, TemplateReflection> $templates
     * @param list<Type\NamedObjectType> $interfaces
     * @param array<non-empty-string, PropertyReflection> $ownProperties
     * @param array<non-empty-string, MethodReflection> $ownMethods
     */
    public function __construct(
        public readonly string $name,
        public readonly array $templates,
        public readonly bool $final,
        public readonly bool $abstract,
        public readonly bool $readonly,
        public readonly ?Type\NamedObjectType $parent,
        public readonly array $interfaces,
        private readonly array $ownProperties,
        private readonly array $ownMethods,
    ) {
    }

    /**
     * @param array<Type> $templateArguments
     * @return self<T>
     */
    public function withResolvedTemplates(array $templateArguments = []): self
    {
        if ($this->templates === []) {
            return $this;
        }

        $resolvedTemplateArguments = [];

        foreach ($this->templates as $template) {
            $resolvedTemplateArguments[$template->name] = $templateArguments[$template->name]
                ?? $templateArguments[$template->position]
                ?? $template->constraint;
        }

        /** @var self<T> */
        return $this->withResolvedTypes(new ClassTemplateResolver($this->name, $resolvedTemplateArguments));
    }

    /**
     * @return self<T>
     */
    public function withResolvedStatic(): self
    {
        /** @var self<T> */
        return $this->withResolvedTypes(new StaticResolver($this->name));
    }

    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['properties'], $vars['methods']);

        return $vars;
    }

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        $vars = get_object_vars($this);
        unset($vars['properties'], $vars['methods']);

        $class = new self(...$vars);
        /** @psalm-suppress InaccessibleProperty */
        $class->properties = array_map(
            static fn (PropertyReflection $property): PropertyReflection => $property->withResolvedTypes($typeResolver),
            $this->properties,
        );
        /** @psalm-suppress InaccessibleProperty */
        $class->methods = array_map(
            static fn (MethodReflection $method): MethodReflection => $method->withResolvedTypes($typeResolver),
            $this->methods,
        );

        return $class;
    }

    protected function toChildOf(Reflection $parent): static
    {
        throw new \BadMethodCallException();
    }

    private function load(Reflector $reflector): void
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->properties)) {
            return;
        }

        $parent = null;
        $ancestors = [];

        if ($this->parent !== null) {
            $parent = $reflector->reflectClass($this->parent->class)->withResolvedTemplates($this->parent->templateArguments);
            $ancestors[] = $parent;
        }

        foreach ($this->interfaces as $interface) {
            $ancestors[] = $reflector->reflectClass($interface->class)->withResolvedTemplates($interface->templateArguments);
        }

        $this->loadProperties($parent);
        $this->loadMethods($ancestors);
    }

    private function loadProperties(?self $parent): void
    {
        $properties = $this->ownProperties;

        foreach ($parent?->properties ?? [] as $name => $parentProperty) {
            if ($parentProperty->visibility === Visibility::PRIVATE) {
                continue;
            }

            if (!isset($properties[$name])) {
                $properties[$name] = $parentProperty;

                continue;
            }

            $properties[$name] = $properties[$name]->toChildOf($parentProperty);
        }

        /** @psalm-suppress InaccessibleProperty */
        $this->properties = $properties;
    }

    /**
     * @param list<self> $ancestors
     */
    private function loadMethods(array $ancestors): void
    {
        $methods = $this->ownMethods;

        foreach ($ancestors as $ancestor) {
            foreach ($ancestor->methods as $name => $parentMethod) {
                if ($parentMethod->visibility === Visibility::PRIVATE) {
                    continue;
                }

                if (!isset($methods[$name])) {
                    $methods[$name] = $parentMethod;

                    continue;
                }

                $methods[$name] = $methods[$name]->toChildOf($parentMethod);
            }
        }

        /** @psalm-suppress InaccessibleProperty */
        $this->methods = $methods;
    }
}
