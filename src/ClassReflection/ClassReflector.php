<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassReflection;

use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ClassReflector
{
    /**
     * @template T of object
     * @param class-string<T> $name
     * @return ClassReflection<T>
     * @throws ReflectionException
     */
    public function reflectClass(string $name): ClassReflection;
}
