<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @internal
 * @psalm-internal Typhoon\Collection
 */
final class KeyHasher
{
    public static function hash(mixed $value): int|string
    {
        if (\is_int($value)) {
            if ($value >= 0) {
                return $value;
            }

            return 'i' . $value;
        }

        if (\is_string($value)) {
            if ($value === '') {
                return '';
            }

            return '`' . addcslashes($value, '`') . '`';
        }

        if ($value === null) {
            return -1;
        }

        if ($value === true) {
            return -2;
        }

        if ($value === false) {
            return -3;
        }

        if (\is_object($value)) {
            return -4 - spl_object_id($value);
        }

        if (\is_array($value)) {
            $hash = '[';
            $index = 0;

            foreach ($value as $key => $item) {
                if ($key !== $index) {
                    $hash .= self::hash($key);
                }

                $hash .= self::hash($item) . ',';

                ++$index;
            }

            return $hash . ']';
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        if (\is_resource($value)) {
            return 'r' . get_resource_id($value);
        }

        throw new \LogicException(\sprintf('Type %s is not supported', get_debug_type($value)));
    }
}
