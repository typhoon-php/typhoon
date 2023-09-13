<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

final class ReflectorCompatibilityProvider
{
    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    /**
     * @return array<string, array{string}>
     */
    public static function classes(): array
    {
        $file = __DIR__ . '/ReflectorCompatibility/classes.php';

        include_once $file;

        $classes = NameCollector::collect($file)->classes;

        return array_combine($classes, array_map(
            static fn (string $class): array => [$class],
            $classes,
        ));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function classes82(): array
    {
        $file = __DIR__ . '/ReflectorCompatibility/classes82.php';

        include_once $file;

        $classes = NameCollector::collect($file)->classes;

        return array_combine($classes, array_map(
            static fn (string $class): array => [$class],
            $classes,
        ));
    }
}
