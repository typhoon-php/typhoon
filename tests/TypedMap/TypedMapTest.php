<?php

declare(strict_types=1);

namespace Typhoon\TypedMap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypedMap::class)]
final class TypedMapTest extends TestCase
{
    public function testItReturnsDefaultValueForAnOptionalKey(): void
    {
        $map = new TypedMap();

        $value = $map[OptionalKeys::A];

        self::assertSame(OptionalKeys::DEFAULT, $value);
    }

    public function testItReturnsFalseForNonSetOffset(): void
    {
        $map = new TypedMap();

        self::assertFalse(isset($map[Keys::A]));
    }

    public function testItThrowsWhenRequiredKeyDoesNotExist(): void
    {
        $map = new TypedMap();

        $this->expectExceptionObject(new KeyIsNotDefined(Keys::A));

        $map[Keys::A];
    }

    public function testWithReturnsNewMapWithAddedKey(): void
    {
        $map = TypedMap::one(Keys::A, 123);
        $initialMapCopy = clone $map;

        $newMap = $map->with(Keys::B, 'abc');

        self::assertSame(123, $newMap[Keys::A]);
        self::assertSame('abc', $newMap[Keys::B]);
        self::assertEquals($initialMapCopy, $map);
        self::assertNotSame($map, $newMap);
    }

    public function testWithMapReturnsNewMapWithMergedKeys(): void
    {
        $map1 = TypedMap::one(Keys::A, 123);
        $initialMap1Copy = clone $map1;
        $map2 = TypedMap::one(Keys::B, 'abc');
        $initialMap2Copy = clone $map2;

        $merged = $map1->withMap($map2);

        self::assertSame(123, $merged[Keys::A]);
        self::assertSame('abc', $merged[Keys::B]);
        self::assertEquals($initialMap1Copy, $map1);
        self::assertEquals($initialMap2Copy, $map2);
        self::assertNotSame($map1, $merged);
        self::assertNotSame($map2, $merged);
    }

    public function testItRemovesKeyViaWithout(): void
    {
        $map = TypedMap::one(Keys::A, 123);
        $initialMapCopy = clone $map;

        $newMap = $map->without(Keys::A, Keys::B);

        self::assertCount(0, $newMap);
        self::assertEquals($initialMapCopy, $map);
        self::assertNotSame($map, $newMap);
    }

    public function testWithRemovesOptionalKeyWithDefaultValue(): void
    {
        $map = TypedMap::one(OptionalKeys::A, '123');

        $newMap = $map->with(OptionalKeys::A, OptionalKeys::DEFAULT);

        self::assertCount(1, $map);
        self::assertCount(0, $newMap);
    }

    public function testWithDoesReturnsOldMapWhenOptionalKeyWithDefaultValueIsAdded(): void
    {
        $map = new TypedMap();

        $newMap = $map->with(OptionalKeys::A, OptionalKeys::DEFAULT);

        self::assertSame($map, $newMap);
    }

    public function testWithMapReplacesExistingKeys(): void
    {
        $map = TypedMap::one(Keys::A, 'a')->with(Keys::B, 'b');
        $map2 = TypedMap::one(Keys::A, 'a2');

        $merged = $map->withMap($map2);

        self::assertSame($merged[Keys::A], 'a2');
        self::assertSame($merged[Keys::B], 'b');
    }

    public function testMapCount(): void
    {
        $map = new TypedMap();
        $map2 = TypedMap::one(Keys::A, 'a2');

        self::assertCount(0, $map);
        self::assertCount(1, $map2);
    }

    public function testOffsetSetThrows(): void
    {
        $map = new TypedMap();

        $this->expectExceptionObject(new \BadMethodCallException('Typhoon\TypedMap\TypedMap is immutable'));

        $map[Keys::A] = 'a';
    }

    public function testOffsetUnsetThrows(): void
    {
        $map = new TypedMap();

        $this->expectExceptionObject(new \BadMethodCallException('Typhoon\TypedMap\TypedMap is immutable'));

        unset($map[Keys::A]);
    }

    public function testItDeserializesCorrectly(): void
    {
        $map = TypedMap::one(Keys::A, 'a')->with(Keys::B, new \stdClass());

        $unserialized = unserialize(serialize($map));

        self::assertEquals($map, $unserialized);
    }

    public function testSerializedRepresentationDoesNotChange(): void
    {
        $map = TypedMap::one(Keys::A, 'a')->with(Keys::B, 123);

        self::assertSame(
            'O:25:"Typhoon\TypedMap\TypedMap":2:{s:24:"Typhoon\TypedMap\Keys::A";s:1:"a";s:24:"Typhoon\TypedMap\Keys::B";i:123;}',
            serialize($map),
        );
    }
}
