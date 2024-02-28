<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;

/**
 * @api
 */
final class NativeReflectionLocator implements ClassLocator
{
    public function locateClass(string $name): null|\ReflectionClass
    {
        try {
            return new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return null;
        }
    }
}
