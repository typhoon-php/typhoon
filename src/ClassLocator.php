<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
interface ClassLocator
{
    /**
     * @param non-empty-string $name
     */
    public function locateClass(string $name): null|\ReflectionClass|Resource;
}
