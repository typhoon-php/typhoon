<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\DataStructure
 */
final class UniqueHasher
{
    private const NULL = 'n';
    private const TRUE = 't';
    private const FALSE = 'f';
    private const STRING_QUOTE = '`';
    private const ARRAY_START = '[';
    private const ARRAY_END = ']';
    private const ARRAY_COMMA = ',';
    private const ARRAY_COLON = ':';
    private const OBJECT_ID_PREFIX = '#';
    private const OBJECT_PREFIX_PATTERN = '/^[\w\\\.]+$/';
    private const OBJECT_DATA_PREFIX = '@';
    private const RESOURCE_ID_PREFIX = 'r';

    private static bool $locked = false;

    /**
     * @var ?\Closure(object): non-empty-string
     */
    private static ?\Closure $defaultObjectHasher = null;

    /**
     * @var array<non-empty-string, callable(object): non-empty-string>
     */
    private static array $objectHashers = [];

    private function __construct() {}

    /**
     * @template TObject of object
     * @param class-string<TObject> $class
     * @param non-empty-string $prefix
     * @param callable(TObject): mixed $hasher
     */
    public static function registerObjectHasher(string $class, string $prefix, callable $hasher): void
    {
        if (self::$locked) {
            throw new \LogicException('Please register object hashers before using data structures');
        }

        if (preg_match(self::OBJECT_PREFIX_PATTERN, $prefix) !== 1) {
            throw new \InvalidArgumentException(\sprintf('Invalid prefix "%s"', $prefix));
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        self::$objectHashers[$class] = /** @param TObject $object */ static fn(object $object): string => $prefix . self::OBJECT_DATA_PREFIX . self::hash($hasher($object));
    }

    /**
     * @return int|non-empty-string
     */
    public static function hash(mixed $value): int|string
    {
        self::$locked = true;

        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return self::STRING_QUOTE . addcslashes($value, self::STRING_QUOTE) . self::STRING_QUOTE;
        }

        if (\is_object($value)) {
            $class = $value::class;

            if (isset(self::$objectHashers[$class])) {
                return self::$objectHashers[$class]($value);
            }

            foreach (class_parents($class) as $parent) {
                if (isset(self::$objectHashers[$parent])) {
                    return (self::$objectHashers[$class] = self::$objectHashers[$parent])($value);
                }
            }

            foreach (class_implements($class) as $interface) {
                if (isset(self::$objectHashers[$interface])) {
                    return (self::$objectHashers[$class] = self::$objectHashers[$interface])($value);
                }
            }

            self::$defaultObjectHasher ??= static fn(object $object): string => self::OBJECT_ID_PREFIX . spl_object_id($object);

            return (self::$objectHashers[$class] = self::$defaultObjectHasher)($value);
        }

        if ($value === null) {
            return self::NULL;
        }

        if ($value === true) {
            return self::TRUE;
        }

        if ($value === false) {
            return self::FALSE;
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        if (\is_array($value)) {
            $hash = self::ARRAY_START;

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    $hash .= self::hash($item) . self::ARRAY_COMMA;
                }
            } else {
                foreach ($value as $key => $item) {
                    $hash .= self::hash($key) . self::ARRAY_COLON . self::hash($item) . self::ARRAY_COMMA;
                }
            }

            return $hash . self::ARRAY_END;
        }

        if (\is_resource($value)) {
            return self::RESOURCE_ID_PREFIX . get_resource_id($value);
        }

        throw new \LogicException(\sprintf('Type %s is not supported', get_debug_type($value)));
    }
}
