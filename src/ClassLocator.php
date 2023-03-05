<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Source\Source;

/**
 * @psalm-api
 */
interface ClassLocator
{
    /**
     * @param class-string $class
     */
    public function locateClass(string $class): ?Source;
}
