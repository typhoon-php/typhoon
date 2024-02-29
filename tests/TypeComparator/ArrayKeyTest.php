<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(IsUnion::class)]
#[CoversClass(ComparatorSelector::class)]
final class ArrayKeyTest extends AtomicRelationTestCase
{
    protected static function type(): Type
    {
        return types::arrayKey;
    }

    protected static function subtypes(): iterable
    {
        yield types::arrayKey;
        yield types::never;
        yield types::int;
        yield types::string;
        yield types::nonEmptyString;
        yield types::classString;
        yield types::literalString;
        yield types::truthyString;
        yield types::numericString;
        yield types::intMask(types::literalValue(0));
        yield types::intersection(types::callable, types::string);
        yield types::union(types::nonEmptyString, types::literalValue(1));
    }

    protected static function nonSubtypes(): iterable
    {
        yield types::void;
        yield types::true;
        yield types::false;
        yield types::bool;
        yield types::float;
        yield types::array;
        yield types::iterable;
        yield types::object;
        yield types::callable;
        yield types::closure;
        yield types::resource;
        yield types::mixed;
    }
}
