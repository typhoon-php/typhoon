<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(IsInt::class)]
#[CoversClass(ComparatorSelector::class)]
final class IntTest extends AtomicRelationTestCase
{
    protected static function type(): Type
    {
        return types::int;
    }

    protected static function subtypes(): iterable
    {
        yield types::int;
        yield types::never;
        yield types::positiveInt;
        yield types::negativeInt;
        yield types::nonPositiveInt;
        yield types::nonNegativeInt;
        yield types::literalInt;
        yield types::union(types::literalValue(0), types::literalValue(1));
        yield types::intersection(types::int, types::scalar);
        yield types::intMask(types::literalValue(0));
        yield types::intRange(-100, 99);
    }

    protected static function nonSubtypes(): iterable
    {
        yield types::true;
        yield types::false;
        yield types::bool;
        yield types::arrayKey;
        yield types::float;
        yield types::string;
        yield types::nonEmptyString;
        yield types::classString;
        yield types::literalString;
        yield types::truthyString;
        yield types::numericString;
        yield types::array;
        yield types::iterable;
        yield types::object;
        yield types::callable;
        yield types::closure;
        yield types::resource;
        yield types::intersection(types::callable, types::string);
        yield types::mixed;
        yield types::union(types::void, types::string);
    }
}
