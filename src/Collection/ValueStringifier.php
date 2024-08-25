<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @internal
 * @psalm-internal Typhoon\Collection
 */
final class ValueStringifier
{
    /**
     * @return non-empty-string
     */
    public static function stringify(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            $value === true => 'true',
            $value === false => 'false',
            \is_int($value), \is_float($value) => (string) $value,
            \is_string($value) => \sprintf('"%s"', addcslashes($value, '"')),
            \is_array($value) => self::stringifyArray($value),
            $value instanceof \UnitEnum => \sprintf('%s::%s', $value::class, $value->name),
            \is_object($value) => $value::class,
            \is_resource($value) => 'resource#' . get_resource_id($value),
        };
    }

    /**
     * @return non-empty-string
     */
    private static function stringifyArray(array $values): string
    {
        $string = '[';
        $index = 0;

        foreach ($values as $key => $value) {
            if ($index !== 0) {
                $string .= ', ';
            }

            if ($key !== $index) {
                $string .= self::stringify($key) . ' => ';
            }

            $string .= self::stringify($value);
            ++$index;
        }

        return $string . ']';
    }
}
