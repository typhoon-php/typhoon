<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassReflection;

use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ClassReflector extends ClassExistenceChecker
{
    /**
     * @template T of object
     * @param string|class-string<T>|T $nameOrObject
     * @return ClassReflection<T>
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection;
}
