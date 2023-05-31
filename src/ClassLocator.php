<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

/**
 * @api
 */
interface ClassLocator
{
    /**
     * @param class-string $class
     */
    public function locateClass(string $class): ?Source;
}
