<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use Composer\Autoload\ClassLoader;
use ExtendedTypeSystem\Reflection\ClassLocator;
use ExtendedTypeSystem\Reflection\Source;

/**
 * @api
 */
final class ComposerAutoloadClassLocator implements ClassLocator
{
    private readonly ClassLoader $classLoader;

    public function __construct(
        string|ClassLoader $classLoaderOrAutoloadFilename = __DIR__ . '/../../../autoload.php',
    ) {
        if (\is_string($classLoaderOrAutoloadFilename)) {
            /** @psalm-suppress MixedAssignment, UnresolvableInclude */
            $classLoader = require $classLoaderOrAutoloadFilename;
            \assert($classLoader instanceof ClassLoader);
            $this->classLoader = $classLoader;

            return;
        }

        $this->classLoader = $classLoaderOrAutoloadFilename;
    }

    public function locateClass(string $class): ?Source
    {
        $filename = $this->classLoader->findFile($class);

        if ($filename === false) {
            return null;
        }

        return new Source($filename, file_get_contents($filename));
    }
}
