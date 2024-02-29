<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(IsLiteralValue::class)]
#[CoversClass(ComparatorSelector::class)]
final class LiteralValueTest extends RelationTestCase
{
    protected static function xSubtypeOfY(): iterable
    {
        foreach (self::literalValueTypes() as $literalValueType) {
            yield [$literalValueType, $literalValueType];
            yield [types::never, $literalValueType];
        }

        yield [types::classStringLiteral(\stdClass::class), types::literalValue(\stdClass::class)];
    }

    protected static function xNotSubtypeOfY(): iterable
    {
        foreach (self::literalValueTypes() as $literalValueType) {
            yield [types::void, $literalValueType];
            yield [types::bool, $literalValueType];
            yield [types::int, $literalValueType];
            yield [types::literalInt, $literalValueType];
            yield [types::positiveInt, $literalValueType];
            yield [types::negativeInt, $literalValueType];
            yield [types::intMask(types::literalValue(0)), $literalValueType];
            yield [types::arrayKey, $literalValueType];
            yield [types::float, $literalValueType];
            yield [types::string, $literalValueType];
            yield [types::nonEmptyString, $literalValueType];
            yield [types::classString, $literalValueType];
            yield [types::literalString, $literalValueType];
            yield [types::truthyString, $literalValueType];
            yield [types::numericString, $literalValueType];
            yield [types::array, $literalValueType];
            yield [types::iterable, $literalValueType];
            yield [types::object, $literalValueType];
            yield [types::callable, $literalValueType];
            yield [types::closure, $literalValueType];
            yield [types::resource, $literalValueType];
            yield [types::intersection(types::callable, types::string), $literalValueType];
            yield [types::mixed, $literalValueType];
        }
    }

    /**
     * @return \Generator<Type>
     */
    private static function literalValueTypes(): \Generator
    {
        foreach ([true, false, -10, 12, -12.976, M_PI, 'string', ''] as $value) {
            yield types::literalValue($value);
        }
    }
}
