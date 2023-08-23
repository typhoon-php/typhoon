<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Composer\Autoload\ClassLoader;
use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class ComposerClassLocator implements ClassLocator
{
    public static function isSupported(): bool
    {
        return class_exists(ClassLoader::class);
    }

    public function locateClass(string $name): ?Resource
    {
        foreach (ClassLoader::getRegisteredLoaders() as $classLoader) {
            $file = $classLoader->findFile($name);

            if ($file) {
                return new Resource($file);
            }
        }

        return null;
    }
}
