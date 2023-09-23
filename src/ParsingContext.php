<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
interface ParsingContext
{
    public function parseFile(string $file, ?string $extension = null): void;

    /**
     * @param non-empty-string $name
     * @param callable(): ClassReflection $reflector
     */
    public function registerClassReflector(string $name, callable $reflector): void;
}
