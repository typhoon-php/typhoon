<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ReflectorPsalmTest;

use Typhoon\Reflection\Reflector;

function testReflectClassInfersClassReflectionGenericType(Reflector $reflector): void
{
    $_classReflection = $reflector->reflectClass(\stdClass::class);
    /** @psalm-check-type-exact $_classReflection = \Typhoon\Reflection\ClassReflection<\stdClass> */
}

function testReflectClassAssertsThatStringIsAClass(Reflector $reflector, string $class): void
{
    $reflector->reflectClass($class);
    /** @psalm-check-type-exact $class = class-string */
}

function testReflectObjectInfersClassReflectionGenericType(Reflector $reflector): void
{
    $_classReflection = $reflector->reflectObject(new \stdClass());
    /** @psalm-check-type-exact $_classReflection = \Typhoon\Reflection\ClassReflection<\stdClass> */
}
