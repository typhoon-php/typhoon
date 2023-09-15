<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLoader;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Typhoon\Reflection\ClassLoader;
use Typhoon\Reflection\ParsingContext;

/**
 * @api
 */
final class PhpStormStubsClassLoader implements ClassLoader
{
    private readonly string $directory;

    public function __construct()
    {
        $file = (new \ReflectionClass(PhpStormStubsMap::class))->getFileName();
        $this->directory = \dirname($file);
    }

    public static function isSupported(): bool
    {
        return class_exists(PhpStormStubsMap::class);
    }

    public function loadClass(ParsingContext $parsingContext, string $name): bool
    {
        if (isset(PhpStormStubsMap::CLASSES[$name])) {
            $file = PhpStormStubsMap::CLASSES[$name];

            $parsingContext->parseFile(
                file: $this->directory . '/' . $file,
                extension: substr($file, 0, strpos($file, '/') ?: 0) ?: null,
            );

            return true;
        }

        return false;
    }
}
