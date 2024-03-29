<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

final class FixturesProvider
{
    /**
     * @var ?array<string, array{class-string}>
     */
    private static ?array $classes = null;

    private function __construct() {}

    /**
     * @psalm-suppress PossiblyUnusedReturnValue
     * @return array<string, array{class-string}>
     */
    public static function classes(): array
    {
        if (self::$classes !== null) {
            return self::$classes;
        }

        self::$classes = [
            \Iterator::class => [\Iterator::class],
            \IteratorAggregate::class => [\IteratorAggregate::class],
            \Stringable::class => [\Stringable::class],
            ...self::loadFromFile(__DIR__ . '/Fixtures/classes.php'),
        ];

        if (\PHP_VERSION_ID >= 80200) {
            self::$classes = [
                ...self::$classes,
                ...self::loadFromFile(__DIR__ . '/Fixtures/classes_php82.php'),
            ];
        }

        if (\PHP_VERSION_ID >= 80300) {
            self::$classes = [
                ...self::$classes,
                ...self::loadFromFile(__DIR__ . '/Fixtures/classes_php83.php'),
            ];
        }

        return self::$classes;
    }

    /**
     * @param non-empty-string $file
     * @return array<string, array{class-string}>
     */
    private static function loadFromFile(string $file): array
    {
        $classes = [];

        $declaredClasses = get_declared_classes();

        /** @psalm-suppress UnresolvableInclude */
        require_once $file;

        foreach (array_diff(get_declared_classes(), $declaredClasses) as $class) {
            $classes[str_replace("\0" . __DIR__, '', $class)] = [$class];
        }

        return $classes;
    }
}
