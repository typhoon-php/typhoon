<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLoader;

use Composer\Autoload\ClassLoader as Loader;
use Typhoon\Reflection\ClassLoader;
use Typhoon\Reflection\ParsingContext;

/**
 * @api
 */
final class ComposerClassLoader implements ClassLoader
{
    public static function isSupported(): bool
    {
        return class_exists(Loader::class);
    }

    public function loadClass(ParsingContext $parsingContext, string $name): bool
    {
        foreach (Loader::getRegisteredLoaders() as $loader) {
            $file = $loader->findFile($name);

            if ($file !== false) {
                $parsingContext->parseFile($file);

                return true;
            }
        }

        return false;
    }
}
