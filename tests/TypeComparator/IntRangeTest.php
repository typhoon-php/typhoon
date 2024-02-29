<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\types;

#[CoversClass(IsIntRange::class)]
#[CoversClass(ComparatorSelector::class)]
final class IntRangeTest extends RelationTestCase
{
    protected static function xSubtypeOfY(): iterable
    {
        yield [types::never, types::intRange(-9, 10)];
        yield [types::intRange(-9, 10), types::intRange(-9, 10)];
        yield [types::intRange(-9, 10), types::intRange(-100, 100)];
        yield [types::intRange(-9, 10), types::intRange(max: 100)];
        yield [types::intRange(max: 100), types::intRange(max: 100)];
        yield [types::intRange(max: 99), types::intRange(max: 100)];
        yield [types::intRange(-9, 10), types::intRange(min: -100)];
        yield [types::intRange(min: -100), types::intRange(min: -100)];
        yield [types::intRange(min: -9), types::intRange(min: -100)];
        yield [types::literalValue(1), types::intRange(0, 1)];
    }

    protected static function xNotSubtypeOfY(): iterable
    {
        yield [types::void, types::intRange(-1, 1)];
        yield [types::true, types::intRange(-1, 1)];
        yield [types::false, types::intRange(-1, 1)];
        yield [types::bool, types::intRange(-1, 1)];
        yield [types::int, types::intRange(-1, 1)];
        yield [types::literalInt, types::intRange(-1, 1)];
        yield [types::positiveInt, types::intRange(-1, 1)];
        yield [types::negativeInt, types::intRange(-1, 1)];
        yield [types::intMask(types::literalValue(0)), types::intRange(-1, 1)];
        yield [types::arrayKey, types::intRange(-1, 1)];
        yield [types::float, types::intRange(-1, 1)];
        yield [types::string, types::intRange(-1, 1)];
        yield [types::nonEmptyString, types::intRange(-1, 1)];
        yield [types::classString, types::intRange(-1, 1)];
        yield [types::literalString, types::intRange(-1, 1)];
        yield [types::truthyString, types::intRange(-1, 1)];
        yield [types::numericString, types::intRange(-1, 1)];
        yield [types::array, types::intRange(-1, 1)];
        yield [types::iterable, types::intRange(-1, 1)];
        yield [types::object, types::intRange(-1, 1)];
        yield [types::callable, types::intRange(-1, 1)];
        yield [types::closure, types::intRange(-1, 1)];
        yield [types::resource, types::intRange(-1, 1)];
        yield [types::intersection(types::callable, types::string), types::intRange(-1, 1)];
        yield [types::mixed, types::intRange(-1, 1)];
    }
}
