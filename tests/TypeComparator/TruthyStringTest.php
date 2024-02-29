<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(IsTruthyString::class)]
#[CoversClass(ComparatorSelector::class)]
final class TruthyStringTest extends AtomicRelationTestCase
{
    protected static function type(): Type
    {
        return types::truthyString;
    }

    protected static function subtypes(): iterable
    {
        yield types::truthyString;
        yield types::never;
        yield types::literalValue('abc');
        yield types::nonEmpty(types::truthyString);
        yield types::classString;
        yield types::classString(types::object(\stdClass::class));
        yield types::classStringLiteral(\stdClass::class);
    }

    protected static function nonSubtypes(): iterable
    {
        yield types::void;
        yield types::true;
        yield types::false;
        yield types::bool;
        yield types::int;
        yield types::literalInt;
        yield types::positiveInt;
        yield types::negativeInt;
        yield types::intMask(types::literalValue(0));
        yield types::arrayKey;
        yield types::float;
        yield types::string;
        yield types::nonEmptyString;
        yield types::literalString;
        yield types::numericString;
        yield types::array;
        yield types::iterable;
        yield types::object;
        yield types::callable;
        yield types::closure;
        yield types::resource;
        yield types::intersection(types::callable, types::string);
        yield types::mixed;
        yield types::union(types::never, types::string);
    }
}
