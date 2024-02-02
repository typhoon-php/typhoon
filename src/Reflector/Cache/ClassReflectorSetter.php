<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector\Cache;

use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\Reflector\ClassReflector;
use Typhoon\Reflection\Reflector\RootReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Reflector
 */
final class ClassReflectorSetter
{
    /**
     * @var ?\Closure(ClassReflection, ClassReflector): void
     */
    private static ?\Closure $classSetter = null;

    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    public static function set(RootReflection $reflection, ClassReflector $reflector): void
    {
        if ($reflection instanceof ClassReflection) {
            self::classSetter()($reflection, $reflector);
        }
    }

    /**
     * @psalm-suppress InaccessibleProperty
     * @return \Closure(ClassReflection, ClassReflector): void
     */
    private static function classSetter(): \Closure
    {
        if (self::$classSetter !== null) {
            return self::$classSetter;
        }

        /** @var \Closure(PropertyReflection, ClassReflector): void */
        $propertySetter = \Closure::bind(static function (PropertyReflection $reflection, ClassReflector $classReflector): void {
            $reflection->classReflector = $classReflector;
        }, null, PropertyReflection::class);

        /** @var \Closure(ParameterReflection, ClassReflector): void */
        $parameterSetter = \Closure::bind(static function (ParameterReflection $reflection, ClassReflector $classReflector): void {
            $reflection->classReflector = $classReflector;
        }, null, ParameterReflection::class);

        /** @var \Closure(MethodReflection, ClassReflector): void */
        $methodSetter = \Closure::bind(static function (MethodReflection $reflection, ClassReflector $classReflector) use ($parameterSetter): void {
            $reflection->classReflector = $classReflector;

            foreach ($reflection->parameters as $parameter) {
                $parameterSetter($parameter, $classReflector);
            }
        }, null, MethodReflection::class);

        /** @var \Closure(ClassReflection, ClassReflector): void */
        $classSetter = \Closure::bind(static function (ClassReflection $reflection, ClassReflector $classReflector) use ($propertySetter, $methodSetter): void {
            $reflection->classReflector = $classReflector;

            foreach ($reflection->ownProperties as $ownProperty) {
                $propertySetter($ownProperty, $classReflector);
            }

            foreach ($reflection->ownMethods as $ownMethod) {
                $methodSetter($ownMethod, $classReflector);
            }
        }, null, ClassReflection::class);

        return self::$classSetter = $classSetter;
    }
}
