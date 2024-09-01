<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Typhoon\DataStructure\Internal\ArrayMap;

#[CoversClass(Map::class)]
#[CoversClass(MutableMap::class)]
final class MapTest extends MapTestCase
{
    /**
     * @param class-string<Map> $class
     */
    #[TestWith([Map::class])]
    #[TestWith([MutableMap::class])]
    public function testOf(string $class): void
    {
        $map = $class::of(['a', 'b', 'c']);

        self::assertInstanceOf(ArrayMap::class, $map);
        self::assertSame(['a', 'b', 'c'], $map->toArray());
    }

    /**
     * @param class-string<Map> $class
     */
    #[TestWith([Map::class])]
    #[TestWith([MutableMap::class])]
    public function testFromPairs(string $class): void
    {
        $map = $class::fromPairs(new KVPair('a', 'b'), new KVPair('c', 'd'));

        self::assertInstanceOf(ArrayMap::class, $map);
        self::assertSame(['a' => 'b', 'c' => 'd'], $map->toArray());
    }

    /**
     * @param class-string<Map> $class
     */
    #[TestWith([Map::class])]
    #[TestWith([MutableMap::class])]
    public function testFromKeys(string $class): void
    {
        $map = $class::fromKeys(['a', 'b'], static fn(string $key): string => $key . $key);

        self::assertInstanceOf(ArrayMap::class, $map);
        self::assertSame(['a' => 'aa', 'b' => 'bb'], $map->toArray());
    }

    /**
     * @param class-string<Map> $class
     */
    #[TestWith([Map::class])]
    #[TestWith([MutableMap::class])]
    public function testFromValues(string $class): void
    {
        $map = $class::fromValues(['a', 'b'], static fn(string $value): string => $value . $value);

        self::assertInstanceOf(ArrayMap::class, $map);
        self::assertSame(['aa' => 'a', 'bb' => 'b'], $map->toArray());
    }

    protected static function createMap(iterable|\Closure $values = []): MutableMap
    {
        $map = new SerializedKeyArrayMap();
        $map->putAll($values);

        return $map;
    }
}
