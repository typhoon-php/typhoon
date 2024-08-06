<?php

declare(strict_types=1);

namespace Typhoon\TypedMap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypedMap::class)]
final class TypedMapTest extends TestCase
{
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
}
