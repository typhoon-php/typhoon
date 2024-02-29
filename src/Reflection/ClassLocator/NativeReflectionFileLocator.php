<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\FileResource;

/**
 * @api
 */
final class NativeReflectionFileLocator implements ClassLocator
{
    public function locateClass(string $name): ?FileResource
    {
        try {
            $nativeReflection = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return null;
        }

        $file = $nativeReflection->getFileName();

        if ($file !== false) {
            return new FileResource($file, $nativeReflection->getExtensionName());
        }

        return null;
    }
}
