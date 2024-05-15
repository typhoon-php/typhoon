<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 */
final class Hasher
{
    /**
     * @var array<class-string, callable(object): string>
     */
    private static array $objectNormalizers = [];

    private function __construct() {}

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T): string $normalizer
     */
    public static function registerObjectNormalizer(string $class, callable $normalizer): void
    {
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        self::$objectNormalizers[$class] = $normalizer;
    }

    public static function hash(mixed $value): string
    {
        return hash('xxh3', serialize(self::normalize($value)));
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value === null || \is_scalar($value)) {
            return $value;
        }

        if (\is_array($value)) {
            return array_map(self::normalize(...), $value);
        }

        if (!\is_object($value)) {
            throw new \RuntimeException();
        }

        $class = $value::class;

        if (isset(self::$objectNormalizers[$class])) {
            return self::$objectNormalizers[$class]($value);
        }

        return $value;
    }
}
