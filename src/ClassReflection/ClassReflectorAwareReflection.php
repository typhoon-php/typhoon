<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassReflection;

use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class ClassReflectorAwareReflection
{
    private ?ClassReflector $classReflector = null;

    abstract public function __serialize(): array;

    protected function __initialize(ClassReflector $classReflector): void
    {
        if ($this->classReflector === null) {
            $this->classReflector = $classReflector;

            return;
        }

        if ($this->classReflector !== $classReflector) {
            throw new ReflectionException();
        }
    }

    final protected function classReflector(): ClassReflector
    {
        return $this->classReflector ?? throw new ReflectionException('No class reflector.');
    }
}
