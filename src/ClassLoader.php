<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
interface ClassLoader
{
    /**
     * @param non-empty-string $name
     */
    public function loadClass(ParsingContext $parsingContext, string $name): bool;
}
