<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use PHPUnit\Framework\Attributes\CoversClass;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(IsString::class)]
#[CoversClass(ComparatorSelector::class)]
final class StringTest extends AtomicRelationTestCase
{
    protected static function type(): Type
    {
        return types::string;
    }

    protected static function subtypes(): iterable
    {
        yield types::string;
        yield types::never;
        yield types::nonEmptyString;
        yield types::literalString;
        yield types::classString;
        yield types::literalValue('string');
        yield types::classString(types::object(\stdClass::class));
        yield types::truthyString;
        yield types::classStringLiteral(\stdClass::class);
        yield types::numericString;
        yield types::union(types::literalValue('a'), types::literalValue('b'));
        yield types::intersection(types::callable, types::string);
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
        yield types::array;
        yield types::iterable;
        yield types::object;
        yield types::callable;
        yield types::closure;
        yield types::resource;
        yield types::mixed;
    }
}
