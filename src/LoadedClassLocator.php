<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Source\Source;

/**
 * @psalm-api
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

        return new Source(file_get_contents($filename), $filename);
    }
}
