<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\ClassLocator;
use ExtendedTypeSystem\Reflection\Source;

/**
 * @api
 */
final class DeterministicClassLocator implements ClassLocator
{
    /**
     * @var array<class-string, true>
     */
    private readonly array $classes;

    /**
     * @param class-string ...$classes
     */
    public function __construct(
        private readonly Source $source,
        string ...$classes,
    ) {
        $this->classes = array_fill_keys($classes, true);
    }

    public function locateClass(string $class): ?Source
    {
        if (isset($this->classes[$class])) {
            return $this->source;
        }

        return null;
    }
}
