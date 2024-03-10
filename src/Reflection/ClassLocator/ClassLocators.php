<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\FileResource;

/**
 * @api
 */
final class ClassLocators implements ClassLocator
{
    /**
     * @param iterable<ClassLocator> $classLocators
     */
    public function __construct(
        private readonly iterable $classLocators,
    ) {}

    public function locateClass(string $name): ?FileResource
    {
        foreach ($this->classLocators as $classLocator) {
            $file = $classLocator->locateClass($name);

            if ($file !== null) {
                return $file;
            }
        }

        return null;
    }
}
