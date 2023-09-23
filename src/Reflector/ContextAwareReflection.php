<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ReflectionContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class ContextAwareReflection
{
    abstract protected function setContext(ReflectionContext $reflectionContext): void;
}
