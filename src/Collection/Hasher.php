<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 */
final class Hasher
{
    private static bool $loaded = false;

    /**
     * @var array<class-string, false|callable(object): mixed>
     */
    private static array $objectNormalizers = [];

    private function __construct() {}

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T): mixed $normalizer
     */
    public static function registerObjectNormalizer(string $class, callable $normalizer): void
    {
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        self::$objectNormalizers[$class] = $normalizer;
    }

    public static function hash(mixed $value): string
    {
        return json_encode(self::normalize($value));
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value === null || \is_scalar($value)) {
            return $value;
        }

        if (\is_array($value)) {
            return ['a', array_map(self::normalize(...), $value)];
        }

        if (!\is_object($value)) {
            throw new \RuntimeException();
        }

        $objectNormalizer = self::objectNormalizer($value::class);

        if ($objectNormalizer !== false) {
            return [$value::class, $objectNormalizer($value)];
        }

        return ['s', serialize($value)];
    }

    /**
     * @param class-string $class
     * @return false|callable(object): mixed
     */
    private static function objectNormalizer(string $class): false|callable
    {
        if (!self::$loaded) {
            self::$objectNormalizers[\JsonSerializable::class] = static fn(object $object): object => $object;
            self::$loaded = true;
        }

        if (isset(self::$objectNormalizers[$class])) {
            return self::$objectNormalizers[$class];
        }

        foreach (class_parents($class) as $parent) {
            if (isset(self::$objectNormalizers[$parent])) {
                return self::$objectNormalizers[$class] = self::$objectNormalizers[$parent];
            }
        }

        foreach (class_implements($class) as $interface) {
            if (isset(self::$objectNormalizers[$interface])) {
                return self::$objectNormalizers[$class] = self::$objectNormalizers[$interface];
            }
        }

        return self::$objectNormalizers[$class] = false;
    }
}
