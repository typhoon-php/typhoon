<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\DataStructure
 */
final class Encoder
{
    private const NULL = 'n';
    private const TRUE = 't';
    private const FALSE = 'f';
    private const STRING_QUOTE = '`';
    private const ARRAY_LEFT = '[';
    private const ARRAY_RIGHT = ']';
    private const OBJECT_ID = '#';
    private const RESOURCE = 'r';
    private const PREFIX_ANTI_PATTERN = '/(^(|n|t|f|\d+(\.\d+)?|NAN|INF|#|r)$|[\[\]`])/';

    private static bool $locked = false;

    /**
     * @var ?\Closure(object): non-empty-string
     */
    private static ?\Closure $defaultObjectEncoder = null;

    /**
     * @var array<non-empty-string, callable(object): non-empty-string>
     */
    private static array $objectEncoders = [];

    private function __construct() {}

    /**
     * @template TObject of object
     * @param class-string<TObject> $class
     * @param non-empty-string $prefix
     * @param callable(TObject): mixed $encoder
     */
    public static function registerObjectEncoder(string $class, string $prefix, callable $encoder): void
    {
        if (self::$locked) {
            throw new \LogicException('Please register object encoders before using data structures');
        }

        if (preg_match(self::PREFIX_ANTI_PATTERN, $prefix) !== 0) {
            throw new \LogicException(\sprintf('Invalid prefix "%s"', $prefix));
        }

        /** @psalm-suppress InvalidPropertyAssignmentValue */
        self::$objectEncoders[$class] = /** @param TObject $object */ static fn(object $object): string => $prefix . self::encode($encoder($object));
    }

    /**
     * @return int|non-empty-string
     */
    public static function encode(mixed $value): int|string
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

            if (isset(self::$objectEncoders[$class])) {
                return self::$objectEncoders[$class]($value);
            }

            foreach (class_parents($class) as $parent) {
                if (isset(self::$objectEncoders[$parent])) {
                    return (self::$objectEncoders[$class] = self::$objectEncoders[$parent])($value);
                }
            }

            foreach (class_implements($class) as $interface) {
                if (isset(self::$objectEncoders[$interface])) {
                    return (self::$objectEncoders[$class] = self::$objectEncoders[$interface])($value);
                }
            }

            return (self::$objectEncoders[$class] = (self::$defaultObjectEncoder ??= static fn(object $object): string => self::OBJECT_ID . spl_object_id($object)))($value);
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
            $encoded = self::ARRAY_LEFT;

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    $encoded .= self::encode($item) . ',';
                }
            } else {
                foreach ($value as $key => $item) {
                    $encoded .= self::encode($key) . ':' . self::encode($item) . ',';
                }
            }

            return $encoded . self::ARRAY_RIGHT;
        }

        if (\is_resource($value)) {
            return self::RESOURCE . get_resource_id($value);
        }

        throw new \LogicException(\sprintf('Type %s is not supported', get_debug_type($value)));
    }
}
