<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;

/**
 * @api
 * @deprecated use TyphoonReflector::build($fallbackToNativeReflection) instead
 */
final class NativeReflectionLocator implements ClassLocator
{
    public function locateClass(string $name): ?\ReflectionClass
    {
        try {
            return new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return null;
        }
    }
}
