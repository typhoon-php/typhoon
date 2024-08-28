<?php

declare(strict_types=1);

namespace Typhoon\Collection;

use Typhoon\Collection\Internal\KeyEncoder;
use Typhoon\Collection\Internal\TheList;
use Typhoon\Collection\Internal\TheOrderedMap;

/**
 * @api
 * @template T of object
 * @param class-string<T> $class
 * @param callable(T): mixed $normalizer
 */
function registerObjectKeyNormalizer(string $class, callable $normalizer): void
{
    KeyEncoder::registerObjectNormalizer($class, $normalizer);
}

/**
 * @api
 * @template TValue
 * @param iterable<TValue> $values
 * @return List_<TValue>
 */
function listOf(iterable $values = []): List_
{
    return mutableListOf($values);
}

/**
 * @api
 * @template TValue
 * @param iterable<TValue> $values
 * @return MutableList<TValue>
 */
function mutableListOf(iterable $values = []): MutableList
{
    if ($values instanceof \Traversable) {
        $values = iterator_to_array($values, preserve_keys: false);
    } else {
        $values = array_values($values);
    }

    return new TheList($values);
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<TKey, TValue>|callable(): iterable<TKey, TValue> $values
 * @return OrderedMap<TKey, TValue>
 */
function orderedMapOf(iterable|callable $values = []): OrderedMap
{
    return mutableOrderedMapOf($values);
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<TKey, TValue>|callable(): iterable<TKey, TValue> $values
 * @return MutableOrderedMap<TKey, TValue>
 */
function mutableOrderedMapOf(iterable|callable $values = []): MutableOrderedMap
{
    if (\is_callable($values)) {
        $values = $values();
    }

    return TheOrderedMap::fromValues($values);
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<TKey> $keys
 * @param callable(TKey): TValue $valueFactory
 * @return OrderedMap<TKey, TValue>
 */
function orderedMapFromKeys(iterable $keys, callable $valueFactory): OrderedMap
{
    return mutableOrderedMapFromKeys($keys, $valueFactory);
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<TKey> $keys
 * @param callable(TKey): TValue $valueFactory
 * @return MutableOrderedMap<TKey, TValue>
 */
function mutableOrderedMapFromKeys(iterable $keys, callable $valueFactory): MutableOrderedMap
{
    return TheOrderedMap::fromValues((static function () use ($keys, $valueFactory): \Generator {
        foreach ($keys as $key) {
            yield $key => $valueFactory($key);
        }
    })());
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<TValue> $values
 * @param callable(TValue): TKey $keyFactory
 * @return OrderedMap<TKey, TValue>
 */
function orderedMapFromValues(iterable $values, callable $keyFactory): OrderedMap
{
    return mutableOrderedMapFromValues($values, $keyFactory);
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<TValue> $values
 * @param callable(TValue): TKey $keyFactory
 * @return MutableOrderedMap<TKey, TValue>
 */
function mutableOrderedMapFromValues(iterable $values, callable $keyFactory): MutableOrderedMap
{
    return TheOrderedMap::fromValues((static function () use ($values, $keyFactory): \Generator {
        foreach ($values as $value) {
            yield $keyFactory($value) => $value;
        }
    })());
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<array{TKey, TValue}> $keyValuePairs
 * @return OrderedMap<TKey, TValue>
 */
function orderedMapFromKeyValuePairs(iterable $keyValuePairs): OrderedMap
{
    return mutableOrderedMapFromKeyValuePairs($keyValuePairs);
}

/**
 * @api
 * @template TKey
 * @template TValue
 * @param iterable<array{TKey, TValue}> $keyValuePairs
 * @return MutableOrderedMap<TKey, TValue>
 */
function mutableOrderedMapFromKeyValuePairs(iterable $keyValuePairs): MutableOrderedMap
{
    return TheOrderedMap::fromValues((static function () use ($keyValuePairs): \Generator {
        foreach ($keyValuePairs as [$key, $value]) {
            yield $key => $value;
        }
    })());
}
