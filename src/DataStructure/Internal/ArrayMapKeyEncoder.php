<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use Typhoon\DataStructure\ObjectNormalizers;

/**
 * @internal
 * @psalm-internal Typhoon\DataStructure
 */
final class ArrayMapKeyEncoder
{
    private function __construct() {}

    /**
     * @return int|non-empty-string
     */
    public static function encode(mixed $value): int|string
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return '`' . addcslashes($value, '`') . '`';
        }

        if (\is_object($value)) {
            $normalizer = ObjectNormalizers::get($value::class);

            if ($normalizer === null) {
                return '#' . spl_object_id($value);
            }

            return $value::class . ':' . self::encode($normalizer($value));
        }

        if ($value === null) {
            return 'n';
        }

        if ($value === true) {
            return 't';
        }

        if ($value === false) {
            return 'f';
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        if (\is_array($value)) {
            $encoded = '[';

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    $encoded .= self::encode($item) . ',';
                }
            } else {
                foreach ($value as $key => $item) {
                    $encoded .= self::encode($key) . ':' . self::encode($item) . ',';
                }
            }

            return $encoded . ']';
        }

        if (\is_resource($value)) {
            return 'r' . get_resource_id($value);
        }

        throw new \LogicException(\sprintf('Type %s is not supported', get_debug_type($value)));
    }
}
