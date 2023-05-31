<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\ClassLocator;
use ExtendedTypeSystem\Reflection\Source;

/**
 * @api
 */
final class LoadedClassLocator implements ClassLocator
{
    public function locateClass(string $class): ?Source
    {
        if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
            return null;
        }

        $reflectionClass = new \ReflectionClass($class);
        $filename = $reflectionClass->getFileName();

        if ($filename === false) {
            return null;
        }

        return new Source($filename, file_get_contents($filename));
    }
}
