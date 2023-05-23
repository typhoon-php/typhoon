<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\ClassLocator;

use ExtendedTypeSystem\ClassLocator;
use ExtendedTypeSystem\Source;

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
