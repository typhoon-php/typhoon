<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class PropertyInheritanceResolver
{
    private ?PropertyMetadata $property = null;

    private TypeInheritanceResolver $type;

    public function __construct()
    {
        $this->type = new TypeInheritanceResolver();
    }

    public function setOwn(PropertyMetadata $property): void
    {
        $this->property = $property;
        $this->type->setOwn($property->type);
    }

    /**
     * @param TypeVisitor<Type> $templateResolver
     */
    public function addInherited(PropertyMetadata $property, TypeVisitor $templateResolver): void
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
