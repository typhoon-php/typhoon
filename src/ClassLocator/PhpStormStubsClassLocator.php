<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class PhpStormStubsClassLocator implements ClassLocator
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

    public function locateClass(string $name): null|Resource|\ReflectionClass
    {
        if (isset(PhpStormStubsMap::CLASSES[$name])) {
            $file = PhpStormStubsMap::CLASSES[$name];

            return new Resource(
                file: $this->directory . '/' . $file,
                extension: substr($file, 0, strpos($file, '/') ?: 0) ?: null,
            );
        }

        return null;
    }
}
