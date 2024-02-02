<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ReflectorPsalmTest;

use Typhoon\Reflection\TyphoonReflector;

function testReflectClassInfersClassReflectionGenericType(TyphoonReflector $reflector): void
{
    $_classReflection = $reflector->reflectClass(\stdClass::class);
    /** @psalm-check-type-exact $_classReflection = \Typhoon\Reflection\ClassReflection<\stdClass> */
}

function testReflectClassPreservesObjectType(TyphoonReflector $reflector): void
{
    $object = new \stdClass();
    $reflector->reflectClass($object);
    /** @psalm-check-type-exact $object = \stdClass */
}

function testClassExistsAssertsThatStringIsClass(TyphoonReflector $reflector, string $class): void
{
    if ($reflector->classExists($class)) {
        /** @psalm-check-type-exact $class = \class-string */
    }
}

function testClassExistsDoesNotBreakInitialClassType(TyphoonReflector $reflector): void
{
    $class = \stdClass::class;

    if ($reflector->classExists($class)) {
        /** @psalm-check-type-exact $class = \class-string<\stdClass> */
    }
}
