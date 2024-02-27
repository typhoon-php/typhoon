<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\InheritedName;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\TypeResolver\TemplateResolver;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-import-type ClassMetadataReflector from ClassMetadata
 */
final class PropertiesInheritanceResolver
{
    /**
     * @var array<non-empty-string, PropertyInheritanceResolver>
     */
    private array $properties = [];

    /**
     * @param non-empty-string $class
     * @param ?non-empty-string $parent
     * @param ClassMetadataReflector $classMetadataReflector
     */
    public function __construct(
        private readonly \Closure $classMetadataReflector,
        private readonly string $class,
        private readonly ?string $parent,
        private readonly bool $final,
    ) {}

    /**
     * @param iterable<PropertyMetadata> $properties
     */
    public function setOwn(iterable $properties): void
    {
        foreach ($properties as $property) {
            $this->property($property->name)->setOwn($property);
        }
    }

    public function addUsed(InheritedName ...$names): void
    {
        foreach ($names as $name) {
            $class = ($this->classMetadataReflector)($name->class);
            $templateResolver = TemplateResolver::create(
                templates: $class->templates,
                templateArguments: $name->templateArguments,
                self: $this->class,
                parent: $this->parent,
                resolveStatic: $this->final,
            );

            foreach ($class->resolvedProperties($this->classMetadataReflector) as $property) {
                $this->property($property->name)->addUsed($property, $templateResolver);
            }
        }
    }

    public function addInherited(InheritedName ...$names): void
    {
        foreach ($names as $name) {
            $class = ($this->classMetadataReflector)($name->class);
            $templateResolver = TemplateResolver::create(
                templates: $class->templates,
                templateArguments: $name->templateArguments,
                self: $this->class,
                parent: $this->parent,
                resolveStatic: $this->final,
            );

            foreach ($class->resolvedProperties($this->classMetadataReflector) as $property) {
                $this->property($property->name)->addInherited($property, $templateResolver);
            }
        }
    }

    /**
     * @return array<non-empty-string, PropertyMetadata>
     */
    public function resolve(): array
    {
        return array_filter(
            array_map(
                static fn(PropertyInheritanceResolver $resolver): ?PropertyMetadata => $resolver->resolve(),
                $this->properties,
            ),
        );
    }

    /**
     * @param non-empty-string $name
     */
    private function property(string $name): PropertyInheritanceResolver
    {
        return $this->properties[$name] ??= new PropertyInheritanceResolver($this->class);
    }
}
