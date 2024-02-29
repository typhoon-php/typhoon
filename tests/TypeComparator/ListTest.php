<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\types;

#[CoversClass(IsList::class)]
#[CoversClass(ComparatorSelector::class)]
final class ListTest extends RelationTestCase
{
    protected static function xSubtypeOfY(): iterable
    {
        yield [types::never, types::list(types::mixed)];
        yield [types::list(types::int), types::list(types::mixed)];
        yield [types::arrayShape(), types::list(types::mixed)];
        yield [types::arrayShape([1 => types::literalValue('a'), 0 => types::literalValue('b')]), types::list(types::string)];
    }

    protected static function xNotSubtypeOfY(): iterable
    {
        yield [types::list(types::string), types::list(types::nonEmptyString)];
        yield [types::true, types::list(types::mixed)];
        yield [types::false, types::list(types::mixed)];
        yield [types::bool, types::list(types::mixed)];
        yield [types::int, types::list(types::mixed)];
        yield [types::literalInt, types::list(types::mixed)];
        yield [types::positiveInt, types::list(types::mixed)];
        yield [types::negativeInt, types::list(types::mixed)];
        yield [types::intMask(types::literalValue(0)), types::list(types::mixed)];
        yield [types::arrayKey, types::list(types::mixed)];
        yield [types::float, types::list(types::mixed)];
        yield [types::string, types::list(types::mixed)];
        yield [types::nonEmptyString, types::list(types::mixed)];
        yield [types::classString, types::list(types::mixed)];
        yield [types::literalString, types::list(types::mixed)];
        yield [types::truthyString, types::list(types::mixed)];
        yield [types::numericString, types::list(types::mixed)];
        yield [types::array, types::list(types::mixed)];
        yield [types::iterable, types::list(types::mixed)];
        yield [types::object, types::list(types::mixed)];
        yield [types::callable, types::list(types::mixed)];
        yield [types::closure, types::list(types::mixed)];
        yield [types::resource, types::list(types::mixed)];
        yield [types::intersection(types::callable, types::string), types::list(types::mixed)];
        yield [types::mixed, types::list(types::mixed)];
    }
}
