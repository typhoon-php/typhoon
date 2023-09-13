<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

final class ReflectorCompatibilityProvider
{
    private const CLASSES = __DIR__ . '/ReflectorCompatibility/classes.php';
    private const READONLY_CLASSES = __DIR__ . '/ReflectorCompatibility/readonly_classes.php';

    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function classes(): \Generator
    {
        include_once self::CLASSES;

        foreach (NameCollector::collect(self::CLASSES)->classes as $class) {
            yield $class => [self::CLASSES, $class];
        }
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function readonlyClasses(): \Generator
    {
        foreach (NameCollector::collect(self::READONLY_CLASSES)->classes as $class) {
            yield $class => [self::READONLY_CLASSES, $class];
        }
    }
}
