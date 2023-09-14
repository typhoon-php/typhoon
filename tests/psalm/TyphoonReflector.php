<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ReflectorPsalmTest;

use Typhoon\Reflection\TyphoonReflector;

function testReflectClassInfersClassReflectionGenericType(TyphoonReflector $reflector): void
{
    $_classReflection = $reflector->reflectClass(\stdClass::class);
    /** @psalm-check-type-exact $_classReflection = \Typhoon\Reflection\ClassReflection<\stdClass> */
}

function testReflectClassAssertsThatStringIsAClass(TyphoonReflector $reflector, string $class): void
{
    $reflector->reflectClass($class);
    /** @psalm-check-type-exact $class = class-string */
}

function testReflectObjectInfersClassReflectionGenericType(TyphoonReflector $reflector): void
{
    $_classReflection = $reflector->reflectObject(new \stdClass());
    /** @psalm-check-type-exact $_classReflection = \Typhoon\Reflection\ClassReflection<\stdClass> */
}
