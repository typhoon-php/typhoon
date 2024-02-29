<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\types;

#[CoversClass(IsUnion::class)]
#[CoversClass(ComparatorSelector::class)]
final class UnionTest extends RelationTestCase
{
    protected static function xSubtypeOfY(): iterable
    {
        yield [types::union(types::object, types::bool), types::union(types::object, types::bool)];
        yield [types::never, types::union(types::object, types::bool)];
        yield [types::object, types::union(types::object, types::bool)];
    }

    protected static function xNotSubtypeOfY(): iterable
    {
        yield [types::void, types::union(types::object, types::callable)];
        yield [types::true, types::union(types::object, types::callable)];
        yield [types::false, types::union(types::object, types::callable)];
        yield [types::bool, types::union(types::object, types::callable)];
        yield [types::int, types::union(types::object, types::callable)];
        yield [types::literalInt, types::union(types::object, types::callable)];
        yield [types::positiveInt, types::union(types::object, types::callable)];
        yield [types::negativeInt, types::union(types::object, types::callable)];
        yield [types::intMask(types::literalValue(0)), types::union(types::object, types::callable)];
        yield [types::arrayKey, types::union(types::object, types::callable)];
        yield [types::float, types::union(types::object, types::callable)];
        yield [types::string, types::union(types::object, types::callable)];
        yield [types::nonEmptyString, types::union(types::object, types::callable)];
        yield [types::classString, types::union(types::object, types::callable)];
        yield [types::literalString, types::union(types::object, types::callable)];
        yield [types::truthyString, types::union(types::object, types::callable)];
        yield [types::numericString, types::union(types::object, types::callable)];
        yield [types::array, types::union(types::object, types::callable)];
        yield [types::iterable, types::union(types::object, types::callable)];
        yield [types::callable, types::union(types::object, types::callable)];
        yield [types::resource, types::union(types::object, types::callable)];
        yield [types::intersection(types::callable, types::string), types::union(types::object, types::callable)];
        yield [types::mixed, types::union(types::object, types::callable)];
    }
}
