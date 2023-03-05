<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Source;

use Composer\Autoload\ClassLoader;
use ExtendedTypeSystem\ClassLocator;

/**
 * @psalm-api
 */
final class ComposerAutoloadClassLocator implements ClassLocator
{
    private readonly ClassLoader $classLoader;

    public function __construct(
        string|ClassLoader $classLoaderOrAutoloadFilename = __DIR__.'/../../../autoload.php',
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

        return new Source(file_get_contents($filename), $filename);
    }
}
