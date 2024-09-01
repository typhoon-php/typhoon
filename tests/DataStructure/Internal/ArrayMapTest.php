<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\DataStructure\MapTestCase;
use Typhoon\DataStructure\MutableMap;

#[CoversClass(ArrayMap::class)]
final class ArrayMapTest extends MapTestCase
{
    protected static function createMap(iterable|\Closure $values = []): MutableMap
    {
        $map = new ArrayMap();
        $map->putAll($values);

        return $map;
    }
}
