<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\FileResource;

/**
 * @api
 */
final class PhpStormStubsClassLocator implements ClassLocator
{
    private readonly string $directory;

    public function __construct()
    {
        $file = (new \ReflectionClass(PhpStormStubsMap::class))->getFileName();
        \assert($file !== false, sprintf('Failed to locate class %s.', PhpStormStubsMap::class));
        $this->directory = \dirname($file);
    }

    public static function isSupported(): bool
    {
        return class_exists(PhpStormStubsMap::class);
    }

    public function locateClass(string $name): ?FileResource
    {
        if (isset(PhpStormStubsMap::CLASSES[$name])) {
            $file = PhpStormStubsMap::CLASSES[$name];

            return new FileResource($this->directory . '/' . $file, \dirname($file));
        }

        return null;
    }
}
