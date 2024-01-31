<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class ContextAwareReflection
{
    abstract protected function setClassReflector(ClassReflector $classReflector): void;
}
