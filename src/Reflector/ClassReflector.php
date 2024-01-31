<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ClassReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ClassReflector
{
    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    public function reflectClass(string $name): ClassReflection;
}
