<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\TypeResolver\TemplateResolver;
use Typhoon\Type\NamedObjectType;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PropertiesInheritanceResolver
{
    /**
     * @var array<non-empty-string, PropertyInheritanceResolver>
     */
    private array $properties = [];

    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
        private readonly ClassReflector $classReflector,
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

    public function addUsed(NamedObjectType ...$types): void
    {
        foreach ($types as $type) {
            $class = $this->classReflector->reflectClass($type->class);
            $templateResolver = TemplateResolver::create($class->getTemplates(), $type->templateArguments);

            foreach ($class->getProperties() as $property) {
                $this->property($property->name)->addUsed($property->__metadata(), $templateResolver);
            }
        }
    }

    public function addInherited(NamedObjectType ...$types): void
    {
        foreach ($types as $type) {
            $class = $this->classReflector->reflectClass($type->class);
            $templateResolver = TemplateResolver::create($class->getTemplates(), $type->templateArguments);

            foreach ($class->getProperties() as $property) {
                $this->property($property->name)->addInherited($property->__metadata(), $templateResolver);
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
