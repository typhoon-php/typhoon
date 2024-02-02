<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\Resource;

final class NativeReflectionLocator implements ClassLocator
{
    public function locateClass(string $name): null|Resource|\ReflectionClass
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $nativeReflection = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return null;
        }

        $file = $nativeReflection->getFileName();

        if ($file !== false) {
            return new Resource($file, $nativeReflection->getExtensionName() ?: null);
        }

        return $nativeReflection;
    }
}
