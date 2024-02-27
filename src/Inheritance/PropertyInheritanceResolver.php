<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\TypeResolver\TemplateResolver;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class PropertyInheritanceResolver
{
    private ?PropertyMetadata $property = null;

    private TypeInheritanceResolver $type;

    /**
     * @param non-empty-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {
        $this->type = new TypeInheritanceResolver();
    }

    public function setOwn(PropertyMetadata $property): void
    {
        $this->property = $property;
        $this->type->setOwn($property->type);
    }

    public function addUsed(PropertyMetadata $property, TemplateResolver $templateResolver): void
    {
        $this->property ??= $property->withClass($this->class);
        $this->type->addInherited($property->type, $templateResolver);
    }

    public function addInherited(PropertyMetadata $property, TemplateResolver $templateResolver): void
    {
        if ($property->modifiers & \ReflectionProperty::IS_PRIVATE) {
            return;
        }

        $this->property ??= $property;
        $this->type->addInherited($property->type, $templateResolver);
    }

    public function resolve(): ?PropertyMetadata
    {
        if ($this->property === null) {
            return null;
        }

        return $this->property->withType($this->type->resolve());
    }
}
