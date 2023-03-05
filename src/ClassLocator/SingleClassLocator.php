<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\ClassLocator;

use ExtendedTypeSystem\ClassLocator;
use ExtendedTypeSystem\Source;

/**
 * @psalm-api
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
