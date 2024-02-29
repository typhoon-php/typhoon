<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassReflection;

use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ClassReflector extends ClassExistenceChecker
{
    /**
     * @param non-empty-string $name
     * @throws ReflectionException
     */
    public function reflectClass(string $name): ClassReflection;
}
