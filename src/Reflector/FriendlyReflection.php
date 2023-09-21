<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class FriendlyReflection
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
