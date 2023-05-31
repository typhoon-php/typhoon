<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 * @psalm-immutable
 */
final class PropertyReflection
{
    public function __construct(
        public readonly bool $private,
        public readonly TypeReflection $type,
    ) {
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function resolveTypes(TypeVisitor $typeResolver): self
    {
        return new self(
            private: $this->private,
            type: $this->type->resolveTypes($typeResolver),
        );
    }
}
