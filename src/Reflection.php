<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

abstract class Reflection
{
    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    abstract protected function withResolvedTypes(TypeVisitor $typeResolver): static;

    /**
     * @param static $parent
     */
    abstract protected function toChildOf(self $parent): static;
}
