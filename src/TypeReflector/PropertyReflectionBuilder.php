<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\PropertyReflection;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection\TypeReflector
 */
final class PropertyReflectionBuilder
{
    public readonly TypeReflectionBuilder $type;
    private bool $private = false;

    public function __construct()
    {
        $this->type = new TypeReflectionBuilder();
    }

    public function private(bool $private): self
    {
        $this->private = $private;

        return $this;
    }

    public function addPrototype(PropertyReflection $property): self
    {
        $this->type->addPrototype($property->type);

        return $this;
    }

    public function build(): PropertyReflection
    {
        return new PropertyReflection(
            private: $this->private,
            type: $this->type->build(),
        );
    }
}
