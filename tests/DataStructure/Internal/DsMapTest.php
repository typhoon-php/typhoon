<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\DataStructure\MapTestCase;
use Typhoon\DataStructure\MutableMap;

#[CoversClass(DsMap::class)]
final class DsMapTest extends MapTestCase
{
    protected static function createMap(iterable|\Closure $values = []): MutableMap
    {
        $map = new DsMap();
        $map->putAll($values);

        return $map;
    }
}
