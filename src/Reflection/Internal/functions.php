<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TKey of array-key
 * @template TValue
 * @template TNewValue
 * @param iterable<TKey, TValue> $iterable
 * @param callable(TValue, TKey): TNewValue $mapper
 * @return (
 *     $iterable is non-empty-list ? non-empty-list<TNewValue> :
 *     $iterable is list ? list<TNewValue> :
 *     $iterable is non-empty-array ? non-empty-array<TKey, TNewValue> :
 *     array<TKey, TNewValue>
 * )
 */
function map(iterable $iterable, callable $mapper): array
{
    $mapped = [];

    foreach ($iterable as $key => $value) {
        $mapped[$key] = $mapper($value, $key);
    }

    return $mapped;
}

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-pure
 * @template TValue
 * @param array<TValue> $array
 * @return ($array is non-empty-array ? TValue : ?TValue)
 */
function array_value_first(array $array): mixed
{
    $key = array_key_first($array);

    return $key === null ? null : $array[$key];
}

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-pure
 * @template TValue
 * @param array<TValue> $array
 * @return ($array is non-empty-array ? TValue : ?TValue)
 */
function array_value_last(array $array): mixed
{
    $key = array_key_last($array);

    return $key === null ? null : $array[$key];
}

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-assert-if-true class-string $name
 */
function class_like_exists(string $name, bool $autoload = true): bool
{
    return class_exists($name, $autoload) || interface_exists($name, $autoload) || trait_exists($name, $autoload);
}
