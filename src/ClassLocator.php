<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

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
