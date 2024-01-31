<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class ClassReflectorAwareReflection
{
    private ?ClassReflector $classReflector = null;

    abstract public function __serialize(): array;

    protected function setClassReflector(ClassReflector $classReflector): void
    {
        if ($this->classReflector !== null) {
            throw new ReflectionException('Class reflector is already set.');
        }

        $this->classReflector = $classReflector;
    }

    final protected function classReflector(): ClassReflector
    {
        return $this->classReflector ?? throw new ReflectionException('No class reflector.');
    }
}
