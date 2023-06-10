<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\ClassLocator;

/**
 * @api
 */
final class ClassLocatorChain implements ClassLocator
{
    /**
     * @param iterable<ClassLocator> $classLocators
     */
    public function __construct(
        private readonly iterable $classLocators = [],
    ) {
    }

    public function locateClass(string $name): ?string
    {
        foreach ($this->classLocators as $locator) {
            $file = $locator->locateClass($name);

            if ($file !== null) {
                return $file;
            }
        }

        return null;
    }
}
