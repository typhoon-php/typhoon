<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

interface ClassLocator
{
    /**
     * @param non-empty-string $name
     * @return non-empty-string
     */
    public function locateClass(string $name): ?string;
}
