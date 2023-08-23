<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\Resource;

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
    ) {}

    public function locateClass(string $name): ?Resource
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
