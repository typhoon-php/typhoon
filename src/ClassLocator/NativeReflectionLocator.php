<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\FileResource;

/**
 * @api
 */
final class NativeReflectionLocator implements ClassLocator
{
    public function locateClass(string $name): null|FileResource|\ReflectionClass
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $nativeReflection = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return null;
        }

        $file = $nativeReflection->getFileName();

        if ($file !== false) {
            return new FileResource($file, $nativeReflection->getExtensionName());
        }

        return $nativeReflection;
    }
}
