<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use Composer\Autoload\ClassLoader;
use ExtendedTypeSystem\Reflection\ClassLocator;

/**
 * @api
 */
final class ComposerClassLocator implements ClassLocator
{
    public function locateClass(string $name): ?string
    {
        foreach (ClassLoader::getRegisteredLoaders() as $classLoader) {
            $file = $classLoader->findFile($name);

            if ($file) {
                return $file;
            }
        }

        return null;
    }
}
