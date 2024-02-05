<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\PropertyReflection;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class PropertyInheritanceResolver
{
    private ?PropertyReflection $property = null;

    private TypeInheritanceResolver $type;

    public function __construct()
    {
        $this->type = new TypeInheritanceResolver();
    }

    public function setOwn(PropertyReflection $property): void
    {
        $this->property = $property;
        $this->type->setOwn($property->getType());
    }

    /**
     * @param TypeVisitor<Type> $templateResolver
     */
    public function addInherited(PropertyReflection $property, TypeVisitor $templateResolver): void
    {
        $this->property ??= $property;
        $this->type->addInherited($property->getType(), $templateResolver);
    }

    public function resolve(): PropertyReflection
    {
        \assert($this->property !== null);

        return $this->property->withType($this->type->resolve());
    }
}
