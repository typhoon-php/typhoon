<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use Composer\Autoload\ClassLoader;
use ExtendedTypeSystem\Reflection\ClassLocator;
use ExtendedTypeSystem\Reflection\Source;

/**
 * @api
 */
final class ComposerClassLocator implements ClassLocator
{
    public function locateClass(string $class): ?Source
    {
        foreach (ClassLoader::getRegisteredLoaders() as $classLoader) {
            $file = $classLoader->findFile($class);

            if ($file !== false) {
                return Source::fromFile($file, 'composer autoloader');
            }
        }

        return null;
    }
}
