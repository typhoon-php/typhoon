<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\types;

#[CoversClass(IsClassStringLiteral::class)]
#[CoversClass(ComparatorSelector::class)]
final class ClassStringLiteralTest extends RelationTestCase
{
    protected static function xSubtypeOfY(): iterable
    {
        yield [types::literalValue(\stdClass::class), types::literalValue(\stdClass::class)];
        yield [types::literalValue(\stdClass::class), types::classStringLiteral(\stdClass::class)];
    }

    protected static function xNotSubtypeOfY(): iterable
    {
        yield [types::void, types::literalValue(\stdClass::class)];
        yield [types::bool, types::literalValue(\stdClass::class)];
        yield [types::int, types::literalValue(\stdClass::class)];
        yield [types::literalInt, types::literalValue(\stdClass::class)];
        yield [types::positiveInt, types::literalValue(\stdClass::class)];
        yield [types::negativeInt, types::literalValue(\stdClass::class)];
        yield [types::intMask(types::literalValue(0)), types::literalValue(\stdClass::class)];
        yield [types::arrayKey, types::literalValue(\stdClass::class)];
        yield [types::float, types::literalValue(\stdClass::class)];
        yield [types::string, types::literalValue(\stdClass::class)];
        yield [types::nonEmptyString, types::literalValue(\stdClass::class)];
        yield [types::classString, types::literalValue(\stdClass::class)];
        yield [types::literalString, types::literalValue(\stdClass::class)];
        yield [types::truthyString, types::literalValue(\stdClass::class)];
        yield [types::numericString, types::literalValue(\stdClass::class)];
        yield [types::array, types::literalValue(\stdClass::class)];
        yield [types::iterable, types::literalValue(\stdClass::class)];
        yield [types::object, types::literalValue(\stdClass::class)];
        yield [types::callable, types::literalValue(\stdClass::class)];
        yield [types::closure, types::literalValue(\stdClass::class)];
        yield [types::resource, types::literalValue(\stdClass::class)];
        yield [types::intersection(types::callable, types::string), types::literalValue(\stdClass::class)];
        yield [types::mixed, types::literalValue(\stdClass::class)];
    }
}
