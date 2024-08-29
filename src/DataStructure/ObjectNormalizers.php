<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

/**
 * @api
 */
final class ObjectNormalizers
{
    private static bool $locked = false;

    /**
     * @var array<class-string, ?callable>
     */
    private static array $normalizers = [];

    private function __construct() {}

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T): mixed $normalizer
     */
    public static function register(string $class, callable $normalizer): void
    {
        if (self::$locked) {
            throw new \LogicException('Please register all object normalizers at bootstrap before using Typhoon data structures');
        }

        self::$normalizers[$class] = $normalizer;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ?callable(T): mixed
     */
    public static function get(string $class): ?callable
    {
        self::$locked = true;

        if (\array_key_exists($class, self::$normalizers)) {
            /** @var callable(T): mixed */
            return self::$normalizers[$class];
        }

        foreach (class_parents($class) as $parent) {
            if (\array_key_exists($parent, self::$normalizers)) {
                /** @var callable(T): mixed */
                return self::$normalizers[$class] = self::$normalizers[$parent];
            }
        }

        foreach (class_implements($class) as $interface) {
            if (\array_key_exists($interface, self::$normalizers)) {
                /** @var callable(T): mixed */
                return self::$normalizers[$class] = self::$normalizers[$interface];
            }
        }

        return self::$normalizers[$class] = null;
    }
}
