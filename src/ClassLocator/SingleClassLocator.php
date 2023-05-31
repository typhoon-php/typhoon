<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\ClassLocator;
use ExtendedTypeSystem\Reflection\Source;

/**
 * @api
 */
final class SingleClassLocator implements ClassLocator
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
        private readonly Source $source,
    ) {
    }

    public function locateClass(string $class): ?Source
    {
        if ($class === $this->class) {
            return $this->source;
        }

        return null;
    }
}
