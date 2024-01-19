<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLoader;

use Typhoon\Reflection\ClassLoader;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\ParsingContext;
use Typhoon\Reflection\Reflector\NativeReflectionReflector;

/**
 * @api
 */
final class NativeReflectionClassLoader implements ClassLoader
{
    public function __construct(
        private readonly NativeReflectionReflector $nativeReflectionReflector = new NativeReflectionReflector(),
    ) {}

    public function loadClass(ParsingContext $parsingContext, string $name): bool
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflectionClass = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            return false;
        }

        $file = $reflectionClass->getFileName();

        if ($file !== false) {
            $parsingContext->parseFile($file, $reflectionClass->getExtensionName() ?: null);

            return true;
        }

        $parsingContext->registerClassReflector(
            name: $name,
            reflector: fn(): ClassReflection => $this->nativeReflectionReflector->reflectClass($reflectionClass),
        );

        return true;
    }
}
