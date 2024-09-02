<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

abstract class MapTestCase extends TestCase
{
    final protected static function assertMapEquals(array $expected, Map $map): void
    {
        self::assertSame($expected, $map->toArray());
        // check map internal keys are same
        self::assertEquals(static::createMap($expected), $map);
    }

    final public function testWithReturnsNewMapWithAddedElement(): void
    {
        $map = static::createMap();

        $newMap = $map->with('a', 'b');

        self::assertTrue($map->isEmpty());
        self::assertNotSame($map, $newMap);
        self::assertMapEquals(['a' => 'b'], $newMap);
    }

    final public function testWithPairsWithoutArgsReturnsSameObject(): void
    {
        $map = static::createMap();

        $newMap = $map->withPairs();

        self::assertSame($newMap, $map);
    }

    final public function testWithPairsReturnsNewMapWithAddedElements(): void
    {
        $map = static::createMap();

        $newMap = $map->withPairs(new KVPair('a', 'b'), new KVPair('c', 'd'));

        self::assertTrue($map->isEmpty());
        self::assertNotSame($map, $newMap);
        self::assertMapEquals(['a' => 'b', 'c' => 'd'], $newMap);
    }

    final public function testWithAllWithEmptyArrayReturnsSameMap(): void
    {
        $map = static::createMap();

        $newMap = $map->withAll([]);

        self::assertSame($newMap, $map);
    }

    final public function testWithAllWithClosureReturningEmptyArrayReturnsSameMap(): void
    {
        $map = static::createMap();

        $newMap = $map->withAll(static fn(): array => []);

        self::assertSame($newMap, $map);
    }

    final public function testWithAllWithArrayReturnsNewMapWithAddedElements(): void
    {
        $map = static::createMap();

        $newMap = $map->withAll(['a' => 'b']);

        self::assertTrue($map->isEmpty());
        self::assertNotSame($map, $newMap);
        self::assertMapEquals(['a' => 'b'], $newMap);
    }

    final public function testWithAllWithIteratorReturnsNewMapWithAddedElements(): void
    {
        $map = static::createMap();

        $newMap = $map->withAll(new \ArrayIterator(['a' => 'b']));

        self::assertTrue($map->isEmpty());
        self::assertNotSame($map, $newMap);
        self::assertMapEquals(['a' => 'b'], $newMap);
    }

    final public function testWithAllWithClosureIteratorReturnsNewMapWithAddedElements(): void
    {
        $map = static::createMap();

        $newMap = $map->withAll(static fn(): \Generator => yield 'a' => 'b');

        self::assertTrue($map->isEmpty());
        self::assertNotSame($map, $newMap);
        self::assertMapEquals(['a' => 'b'], $newMap);
    }

    final public function testWithAllWithClosureArrayReturnsNewMapWithAddedElements(): void
    {
        $map = static::createMap();

        $newMap = $map->withAll(static fn(): array => ['a' => 'b']);

        self::assertTrue($map->isEmpty());
        self::assertNotSame($map, $newMap);
        self::assertMapEquals(['a' => 'b'], $newMap);
    }

    final public function testWithoutWithoutArgsReturnsSameMap(): void
    {
        $map = static::createMap(['a' => 'b']);

        $newMap = $map->without();

        self::assertSame($newMap, $map);
    }

    final public function testWithoutReturnsNewMapWithRemovedKeys(): void
    {
        $map = static::createMap(['a' => 'b', 'c' => 'd']);

        $newMap = $map->without('c');

        self::assertMapEquals(['a' => 'b', 'c' => 'd'], $map);
        self::assertNotSame($map, $newMap);
        self::assertMapEquals(['a' => 'b'], $newMap);
    }

    final public function testIsEmptyReturnsTrueForEmptyMap(): void
    {
        $map = static::createMap();

        self::assertTrue($map->isEmpty());
    }

    final public function testIsEmptyReturnsFalseForNonEmptyMap(): void
    {
        $map = static::createMap(['a' => 'b']);

        self::assertFalse($map->isEmpty());
    }

    #[TestWith([[], 0])]
    #[TestWith([['a'], 1])]
    #[TestWith([['a', 'b'], 2])]
    #[TestWith([['a', 'a', 'a'], 3])]
    final public function testCountReturnsValidNumberOfElements(array $values, int $expectedCount): void
    {
        $map = static::createMap($values);

        self::assertCount($expectedCount, $map);
    }

    final public function testContainsAndGet(): void
    {
        $keys = [
            null,
            0,
            1,
            -1,
            0.5,
            0.00001,
            NAN,
            INF,
            '',
            'string',
            [1, 2, 3],
            ['a' => 'b'],
            new \stdClass(),
            new \ArrayObject(),
            new \ArrayObject([1, 2, 3]),
            $this,
            STDIN,
            fopen(__FILE__, 'r'),
        ];
        $map = static::createMap(static function () use ($keys): \Generator {
            foreach ($keys as $key) {
                yield $key => serialize($key);
            }
        });

        foreach ($keys as $key) {
            self::assertTrue($map->contains($key));
            self::assertTrue(isset($map[$key]));
            self::assertSame(serialize($key), $map->get($key, 'NO KEY'));
            /** @psalm-suppress PossiblyNullArrayOffset */
            self::assertSame(serialize($key), $map[$key]);
        }
    }

    /**
     * @psalm-suppress UnevaluatedCode
     */
    final public function testContainsReturnsFalseForNonExistingKey(): void
    {
        $map = static::createMap(['a']);

        self::assertFalse(isset($map[1]));
        self::assertFalse($map->contains(1));
    }

    final public function testGetReturnsNullValue(): void
    {
        $map = static::createMap([null]);

        $value = $map->get(0, 'NO KEY');

        self::assertNull($value);
    }

    final public function testGetReturnsDefaultIfKeyDoesNotExist(): void
    {
        $map = static::createMap();

        $value = $map->get(1, 'NO KEY');

        self::assertSame('NO KEY', $value);
    }

    final public function testGetOrCallsDefaultIfKeyDoesNotExist(): void
    {
        $map = static::createMap();
        $exception = new \LogicException('NO KEY', 123, new \RuntimeException());

        $this->expectExceptionObject($exception);

        $map->getOr(1, static fn(): never => throw $exception);
    }

    final public function testOffsetGetThrowsIfKeyDoesNotExist(): void
    {
        $map = static::createMap(['a']);

        $this->expectExceptionObject(new KeyIsNotDefined(1));

        $map[1];
    }

    final public function testFirstReturnsNullForEmptyMap(): void
    {
        $map = static::createMap();

        $first = $map->first();

        self::assertNull($first);
    }

    final public function testFirstReturnsActualFirstKVPair(): void
    {
        $map = static::createMap(['a' => 'b', 'c' => 'd']);

        $first = $map->first();

        self::assertEquals($first, new KVPair('a', 'b'));
    }

    final public function testLastReturnsNullForEmptyMap(): void
    {
        $map = static::createMap();

        $last = $map->last();

        self::assertNull($last);
    }

    final public function testLastReturnsActualLastKVPair(): void
    {
        $map = static::createMap(['a' => 'b', 'c' => 'd']);

        $last = $map->last();

        self::assertEquals($last, new KVPair('c', 'd'));
    }

    #[TestWith([[]])]
    #[TestWith([['a']])]
    final public function testFindFirstReturnsNullIfNothingMatches(array $values): void
    {
        $map = static::createMap($values);

        $first = $map->findFirst(static fn(mixed $value): bool => $value === 'b');

        self::assertNull($first);
    }

    final public function testFindFirstReturnsFirstMatchingKVPair(): void
    {
        $map = static::createMap(['a', 'b', 'b']);

        $first = $map->findFirst(static fn(string $value): bool => $value === 'b');

        self::assertEquals($first, new KVPair(1, 'b'));
    }

    #[TestWith([[]])]
    #[TestWith([['a', 'b']])]
    final public function testFindFirstKVReturnsNullIfNothingMatches(array $values): void
    {
        $map = static::createMap($values);

        $first = $map->findFirstKV(static fn(mixed $key, mixed $value): bool => $key === 0 && $value === 'b');

        self::assertNull($first);
    }

    final public function testFindFirstKVReturnsFirstMatchingKVPair(): void
    {
        $map = static::createMap(['a', 'b', 'b']);

        $first = $map->findFirstKV(static fn(int $key, string $value): bool => $key === 2 && $value === 'b');

        self::assertEquals($first, new KVPair(2, 'b'));
    }

    #[TestWith([[], false])]
    #[TestWith([['a'], true])]
    #[TestWith([['a', 'a'], true])]
    #[TestWith([['b', 'a'], true])]
    final public function testAny(array $values, bool $expected): void
    {
        $map = static::createMap($values);

        $any = $map->any(static fn(mixed $value): bool => $value === 'a');

        self::assertSame($expected, $any);
    }

    #[TestWith([[], false])]
    #[TestWith([['a'], false])]
    #[TestWith([['b', 'a'], true])]
    #[TestWith([['b', 'a', 'a'], true])]
    final public function testAnyKV(array $values, bool $expected): void
    {
        $map = static::createMap($values);

        $any = $map->anyKV(static fn(mixed $key, mixed $value): bool => $key === 1 && $value === 'a');

        self::assertSame($expected, $any);
    }

    #[TestWith([[], true])]
    #[TestWith([['a'], true])]
    #[TestWith([['a', 'a'], true])]
    #[TestWith([['b', 'a'], false])]
    final public function testAll(array $values, bool $expected): void
    {
        $map = static::createMap($values);

        $any = $map->all(static fn(mixed $value): bool => $value === 'a');

        self::assertSame($expected, $any);
    }

    #[TestWith([[], true])]
    #[TestWith([['a'], true])]
    #[TestWith([['b', 'a'], false])]
    #[TestWith([['a', 'a'], true])]
    #[TestWith([['b', 'a', 'a'], false])]
    #[TestWith([['a', 'a', 'a'], false])]
    final public function testAllKV(array $values, bool $expected): void
    {
        $map = static::createMap($values);

        $all = $map->allKV(static fn(mixed $key, mixed $value): bool => $key < 2 && $value === 'a');

        self::assertSame($expected, $all);
    }

    final public function testReduceThrowsForEmptyMap(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Empty map'));

        static::createMap()->reduce(static fn(): bool => true);
    }

    final public function testReduceReturnsCorrectConcatenation(): void
    {
        $map = static::createMap(['a', 'b', 'c']);

        $value = $map->reduce(static fn(string $all, string $value): string => $all . $value);

        self::assertSame('abc', $value);
    }

    final public function testReduceKVThrowsForEmptyMap(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Empty map'));

        static::createMap()->reduceKV(static fn(): bool => true);
    }

    final public function testReduceKVReturnsCorrectConcatenation(): void
    {
        $map = static::createMap(['a', 'b', 'c']);

        $value = $map->reduceKV(static fn(string $all, int $key, string $value): string => $all . $key . $value);

        self::assertSame('a1b2c', $value);
    }

    final public function testReduceReturnsFirstValueIfSingleElementMap(): void
    {
        $map = static::createMap(['a']);

        $value = $map->reduceKV(static fn(): never => self::fail());

        self::assertSame('a', $value);
    }

    final public function testFoldReturnsInitialForEmptyMap(): void
    {
        $map = static::createMap();

        $value = $map->fold('empty', static fn(): string => 'non-empty');

        self::assertSame('empty', $value);
    }

    final public function testFoldReturnsCorrectConcatenation(): void
    {
        $map = static::createMap(['a', 'b', 'c']);

        $value = $map->fold('init', static fn(string $all, string $value): string => $all . $value);

        self::assertSame('initabc', $value);
    }

    final public function testFoldKVReturnsInitialForEmptyMap(): void
    {
        $map = static::createMap();

        $value = $map->foldKV('empty', static fn(): string => 'non-empty');

        self::assertSame('empty', $value);
    }

    final public function testFoldKVReturnsCorrectConcatenation(): void
    {
        $map = static::createMap(['a', 'b', 'c']);

        $value = $map->foldKV('init', static fn(string $all, int $key, string $value): string => $all . $key . $value);

        self::assertSame('init0a1b2c', $value);
    }

    #[TestWith([[], []])]
    #[TestWith([['b'], []])]
    #[TestWith([['a'], ['a']])]
    #[TestWith([['b', 'a'], [1 => 'a']])]
    #[TestWith([['b', 'a', 'c', 'a'], [1 => 'a', 3 => 'a']])]
    final public function testFilter(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->filter(static fn(mixed $value): bool => $value === 'a');

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param list<mixed> $values
     */
    #[TestWith([[], []])]
    #[TestWith([['b'], []])]
    #[TestWith([['b', 'a'], [1 => 'a']])]
    #[TestWith([['b', 'a', 'c', 'a'], [1 => 'a', 3 => 'a']])]
    final public function testFilterKV(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->filterKV(static fn(int $key): bool => ($key % 2) === 1);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param array<string> $values
     */
    #[TestWith([[], []])]
    #[TestWith([['a'], [1]])]
    #[TestWith([['a', '', 'bb'], [1, 0, 2]])]
    final public function testMap(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->map(strlen(...));

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param array<string> $values
     */
    #[TestWith([[], []])]
    #[TestWith([['a'], ['0a']])]
    #[TestWith([['a', 'b'], ['0a', '1b']])]
    #[TestWith([['a' => 'b'], ['a' => 'ab']])]
    final public function testMapKV(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->mapKV(static fn(int|string $key, string $value): string => $key . $value);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param array<string> $values
     */
    #[TestWith([[], []])]
    #[TestWith([['a' => 'a'], [1 => 'a']])]
    #[TestWith([['a' => 'a', '' => '', 'bb' => 'bb'], [1 => 'a', 0 => '', 2 => 'bb']])]
    final public function testMapKey(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->mapKey(strlen(...));

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param array<string> $values
     */
    #[TestWith([[], []])]
    #[TestWith([['a'], ['0a' => 'a']])]
    #[TestWith([['a', 'b'], ['0a' => 'a', '1b' => 'b']])]
    #[TestWith([['a' => 'b'], ['ab' => 'b']])]
    final public function testMapKeyKV(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->mapKeyKV(static fn(int|string $key, string $value): string => $key . $value);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param array<string> $values
     */
    #[TestWith([[], []])]
    #[TestWith([['a', 'b'], ['a' => 'a', 'a2' => 'a', 'b' => 'b', 'b2' => 'b']])]
    final public function testFlatMap(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->flatMap(static fn(string $value): array => [$value => $value, $value . '2' => $value]);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param list<string> $values
     */
    #[TestWith([[], []])]
    #[TestWith([['a', 'b'], [0 => 'a', 10 => 'a', 1 => 'b', 11 => 'b']])]
    final public function testFlatMapKV(array $values, array $expected): void
    {
        $map = static::createMap($values);

        $newMap = $map->flatMapKV(static fn(int $key, string $value): array => [$key => $value, $key + 10 => $value]);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($expected, $newMap);
    }

    /**
     * @param array<array-key> $values
     */
    #[TestWith([[]])]
    #[TestWith([['a']])]
    #[TestWith([['a', 'b', 1]])]
    #[TestWith([['a', 'a']])]
    #[TestWith([['a' => 'b']])]
    final public function testFlip(array $values): void
    {
        $map = static::createMap($values);

        $newMap = $map->flip();

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals(array_flip($values), $newMap);
    }

    #[TestWith([[]])]
    #[TestWith([[1]])]
    #[TestWith([[1, 1, 1]])]
    #[TestWith([[3, 2, 1, 4]])]
    #[TestWith([['a', 'd', 'c']])]
    #[TestWith([['c', 'a', 'a']])]
    #[TestWith([['1', '2', '10', '20']])]
    final public function testSort(array $values): void
    {
        $map = static::createMap($values);
        $sorted = $values;
        asort($sorted);

        $newMap = $map->sort();

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($sorted, $newMap);
    }

    #[TestWith([[]])]
    #[TestWith([[1]])]
    #[TestWith([[1, 1, 1]])]
    #[TestWith([[3, 2, 1, 4]])]
    #[TestWith([['a', 'd', 'c']])]
    #[TestWith([['c', 'a', 'a']])]
    #[TestWith([['1', '2', '10', '20']])]
    final public function testSortDesc(array $values): void
    {
        $map = static::createMap($values);
        $sorted = $values;
        arsort($sorted);

        $newMap = $map->sortDesc();

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($sorted, $newMap);
    }

    #[TestWith([[]])]
    #[TestWith([[1 => 'a', -2 => 'b', 10 => 'c']])]
    #[TestWith([['a' => 1, 'aa' => 2, '0' => 3]])]
    #[TestWith([['1' => 1, '10' => 2, '2' => 3, '20' => 4]])]
    final public function testKsort(array $values): void
    {
        $map = static::createMap($values);
        $sorted = $values;
        ksort($sorted);

        $newMap = $map->ksort();

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($sorted, $newMap);
    }

    #[TestWith([[]])]
    #[TestWith([[1 => 'a', -2 => 'b', 10 => 'c']])]
    #[TestWith([['a' => 1, 'aa' => 2, '0' => 3]])]
    #[TestWith([['1' => 1, '10' => 2, '2' => 3, '20' => 4]])]
    final public function testKsortDesc(array $values): void
    {
        $map = static::createMap($values);
        $sorted = $values;
        krsort($sorted);

        $newMap = $map->ksortDesc();

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals($sorted, $newMap);
    }

    final public function testUsort(): void
    {
        $values = [[2], [1]];
        $map = static::createMap($values);

        $newMap = $map->usort(static fn(array $a, array $b): int => $a[0] <=> $b[0]);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals([1 => [1], 0 => [2]], $newMap);
    }

    final public function testUsortKV(): void
    {
        $values = [-1, -20];
        $map = static::createMap($values);

        $newMap = $map->usortKV(static fn(int $ka, int $va, int $kb, int $vb): int => $ka + $va <=> $kb + $vb);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertMapEquals([1 => -20, 0 => -1], $newMap);
    }

    #[TestWith([0, null])]
    #[TestWith([0, 5])]
    #[TestWith([0, 10])]
    #[TestWith([0, 0])]
    #[TestWith([0, 2])]
    #[TestWith([0, -1])]
    #[TestWith([0, -100])]
    #[TestWith([-1, null])]
    #[TestWith([-2, 0])]
    #[TestWith([-2, 2])]
    #[TestWith([-2, -2])]
    #[TestWith([-2, -20])]
    final public function testSlice(int $offset, ?int $length): void
    {
        $values = range(0, 4);
        $expected = \array_slice($values, $offset, $length, preserve_keys: true);
        $map = static::createMap($values);

        $newMap = $map->slice($offset, $length);

        self::assertNotSame($map, $newMap);
        self::assertMapEquals($values, $map);
        self::assertEquals($this->createMap($expected), $newMap);
    }

    final public function testOffsetSetThrowsBadMethodCall(): void
    {
        $map = static::createMap();

        $this->expectExceptionObject(new \BadMethodCallException());

        $map[0] = 1;
    }

    final public function testOffsetUnsetThrowsBadMethodCall(): void
    {
        $map = static::createMap(['a']);

        $this->expectExceptionObject(new \BadMethodCallException());

        unset($map[0]);
    }

    final public function testPutAddsNewElementAtTheEnd(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);

        $map->put(4, 'd');

        self::assertMapEquals(['a', 'b', 'c', 4 => 'd'], $map);
    }

    final public function testPutReplacesElementAtKey(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);

        $map->put(1, 'b2');

        self::assertMapEquals(['a', 'b2', 'c'], $map);
    }

    final public function testPutPairsAddsNewElementAtTheEnd(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);

        $map->putPairs(new KVPair(4, 'd'));

        self::assertMapEquals(['a', 'b', 'c', 4 => 'd'], $map);
    }

    final public function testPutPairsReplacesElementAtKey(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);

        $map->putPairs(new KVPair(1, 'b2'));

        self::assertMapEquals(['a', 'b2', 'c'], $map);
    }

    final public function testPutAllMapReplacesIndexes(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);
        /** @var MutableMap<int, string> */
        $map2 = static::createMap(['a2', 'b2', 'c2']);

        $map->putAll($map2);

        self::assertMapEquals(['a2', 'b2', 'c2'], $map);
        self::assertMapEquals(['a2', 'b2', 'c2'], $map2);
    }

    final public function testPutAllSupportsMap(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);
        /** @var MutableMap<int, string> */
        $map2 = static::createMap([2 => 'c2', 3 => 'd']);

        $map->putAll($map2);

        self::assertMapEquals(['a', 'b', 'c2', 'd'], $map);
        self::assertMapEquals([2 => 'c2', 3 => 'd'], $map2);
    }

    final public function testRemoveWithoutKeysDoesNotChangeMap(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);

        $map->remove();

        self::assertMapEquals(['a', 'b', 'c'], $map);
    }

    final public function testRemoveKeepsOtherKeys(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);

        $map->remove(1);

        self::assertMapEquals(['a', 2 => 'c'], $map);
    }

    final public function testClear(): void
    {
        /** @var MutableMap<int, string> */
        $map = static::createMap(['a', 'b', 'c']);

        $map->clear();

        self::assertTrue($map->isEmpty());
    }

    /**
     * @template K
     * @template V
     * @param iterable<K, V>|\Closure(): iterable<K, V> $values
     * @return MutableMap<K, V>
     */
    abstract protected static function createMap(iterable|\Closure $values = []): MutableMap;
}
