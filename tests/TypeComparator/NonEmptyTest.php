<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\types;

#[CoversClass(IsNonEmpty::class)]
#[CoversClass(ComparatorSelector::class)]
final class NonEmptyTest extends RelationTestCase
{
    protected static function xSubtypeOfY(): iterable
    {
        yield [types::nonEmptyString, types::nonEmptyString];
        yield [types::never, types::nonEmptyString];
        yield [types::literalValue('abc'), types::nonEmptyString];
        yield [types::nonEmpty(types::int), types::nonEmpty(types::mixed)];
        yield [types::literalValue(1), types::nonEmpty(types::int)];
        yield [types::true, types::nonEmpty(types::bool)];
    }

    protected static function xNotSubtypeOfY(): iterable
    {
        yield [types::void, types::nonEmptyString];
        yield [types::true, types::nonEmptyString];
        yield [types::false, types::nonEmptyString];
        yield [types::bool, types::nonEmptyString];
        yield [types::int, types::nonEmptyString];
        yield [types::literalInt, types::nonEmptyString];
        yield [types::positiveInt, types::nonEmptyString];
        yield [types::negativeInt, types::nonEmptyString];
        yield [types::intMask(types::literalValue(0)), types::nonEmptyString];
        yield [types::arrayKey, types::nonEmptyString];
        yield [types::float, types::nonEmptyString];
        yield [types::string, types::nonEmptyString];
        yield [types::classString, types::nonEmptyString];
        yield [types::literalString, types::nonEmptyString];
        yield [types::truthyString, types::nonEmptyString];
        yield [types::numericString, types::nonEmptyString];
        yield [types::array, types::nonEmptyString];
        yield [types::iterable, types::nonEmptyString];
        yield [types::object, types::nonEmptyString];
        yield [types::callable, types::nonEmptyString];
        yield [types::closure, types::nonEmptyString];
        yield [types::resource, types::nonEmptyString];
        yield [types::intersection(types::callable, types::string), types::nonEmptyString];
        yield [types::mixed, types::nonEmptyString];
    }
}
