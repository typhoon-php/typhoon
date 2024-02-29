<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(IsBool::class)]
#[CoversClass(ComparatorSelector::class)]
final class BoolTest extends AtomicRelationTestCase
{
    protected static function type(): Type
    {
        return types::bool;
    }

    protected static function subtypes(): iterable
    {
        yield types::bool;
        yield types::never;
        yield types::true;
        yield types::false;
    }

    protected static function nonSubtypes(): iterable
    {
        yield types::int;
        yield types::literalInt;
        yield types::positiveInt;
        yield types::negativeInt;
        yield types::intMask(types::literalValue(0));
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
