<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\ClassLocator;

use ExtendedTypeSystem\Reflection\ClassLocator;
use JetBrains\PHPStormStub\PhpStormStubsMap;

final class PhpStormStubsClassLocator implements ClassLocator
{
    private readonly string $directory;

    public function __construct()
    {
        $file = (new \ReflectionClass(PhpStormStubsMap::class))->getFileName();
        $this->directory = \dirname($file);
    }

    public function locateClass(string $name): ?string
    {
        if (isset(PhpStormStubsMap::CLASSES[$name])) {
            return $this->directory . \DIRECTORY_SEPARATOR . PhpStormStubsMap::CLASSES[$name];
        }

        return null;
    }
}
