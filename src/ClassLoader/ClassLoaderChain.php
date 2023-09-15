<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLoader;

use Typhoon\Reflection\ClassLoader;
use Typhoon\Reflection\ParsingContext;

/**
 * @api
 */
final class ClassLoaderChain implements ClassLoader
{
    /**
     * @param iterable<ClassLoader> $classLoaders
     */
    public function __construct(
        private readonly iterable $classLoaders,
    ) {}

    public function loadClass(ParsingContext $parsingContext, string $name): bool
    {
        foreach ($this->classLoaders as $classLoader) {
            if ($classLoader->loadClass($parsingContext, $name)) {
                return true;
            }
        }

        return false;
    }
}
