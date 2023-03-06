<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \ExtendedTypeSystem\TypeStringifier
 */
final class TypeStringifierTest extends TestCase
{
    /**
     * @dataProvider typesAndTheirStringRepresentations
     */
    public function testItStringifiesTypeCorrectly(Type $type, string $expectedString): void
    {
        $asString = TypeStringifier::stringify($type);

        self::assertSame($expectedString, $asString);
    }

    /**
     * @return \Generator<array-key, array{Type, string}>
     */
    public function typesAndTheirStringRepresentations(): \Generator
    {
        yield [types::never, 'never'];
        yield [types::void, 'void'];
        yield [types::mixed, 'mixed'];
        yield [types::null, 'null'];
        yield [types::nullable(types::string), '?string'];
        yield [types::true, 'true'];
        yield [types::false, 'false'];
        yield [types::bool, 'bool'];
        yield [types::int, 'int'];
        yield [types::positiveInt, 'positive-int'];
        yield [types::negativeInt, 'negative-int'];
        yield [types::nonNegativeInt, 'non-negative-int'];
        yield [types::nonPositiveInt, 'non-positive-int'];
        yield [types::intLiteral(123), '123'];
        yield [types::intLiteral(-123), '-123'];
        yield [types::int(min: 23), 'int<23, max>'];
        yield [types::int(max: 23), 'int<min, 23>'];
        yield [types::int(min: -100, max: 234), 'int<-100, 234>'];
        yield [types::float, 'float'];
        yield [types::floatLiteral(0.234), '0.234'];
        yield [types::floatLiteral(-0.234), '-0.234'];
        yield [types::numeric, 'numeric'];
        yield [types::arrayKey, 'array-key'];
        yield [types::numericString, 'numeric-string'];
        yield [types::nonEmptyString, 'non-empty-string'];
        yield [types::string, 'string'];
        yield [types::stringLiteral('abcd'), "'abcd'"];
        yield [types::stringLiteral("a'bcd"), "'a\\'bcd'"];
        yield [types::stringLiteral("a\\\\'bcd"), "'a\\\\\\\\\\'bcd'"];
        yield [types::stringLiteral("\n"), "'\\n'"];
        yield [types::scalar, 'scalar'];
        yield [types::resource, 'resource'];
        yield [types::nonEmptyList(), 'non-empty-list'];
        yield [types::nonEmptyList(types::string), 'non-empty-list<string>'];
        yield [types::list(), 'list'];
        yield [types::list(types::string), 'list<string>'];
        yield [types::nonEmptyArray(), 'non-empty-array'];
        yield [types::nonEmptyArray(valueType: types::string), 'non-empty-array<string>'];
        yield [types::nonEmptyArray(types::string, types::int), 'non-empty-array<string, int>'];
        yield [types::array(), 'array'];
        yield [types::array(valueType: types::string), 'array<string>'];
        yield [types::array(types::string, types::int), 'array<string, int>'];
        yield [types::shape(), 'list{}'];
        yield [types::shape(sealed: false), 'array'];
        yield [types::shape([types::int]), 'list{int}'];
        yield [types::shape([types::int, 'a' => types::string]), 'array{0: int, a: string}'];
        yield [types::shape([types::int, 'a' => types::string], sealed: false), 'array{0: int, a: string, ...}'];
        yield [types::shape(['' => types::string]), "array{'': string}"];
        yield [types::shape(['\'' => types::string]), "array{'\\'': string}"];
        yield [types::shape(["\n" => types::string]), "array{'\\n': string}"];
        yield [types::unsealedShape([types::int, 'a' => types::string]), 'array{0: int, a: string, ...}'];
        yield [types::shape([types::optional(types::int)]), 'list{0?: int}'];
        yield [types::unsealedShape([types::optional(types::int)]), 'array{0?: int, ...}'];
        yield [types::shape(['a' => types::optional(types::int)]), 'array{a?: int}'];
        yield [types::object, 'object'];
        yield [types::object(\ArrayObject::class), 'ArrayObject'];
        yield [types::object(\ArrayObject::class, types::arrayKey, types::string), 'ArrayObject<array-key, string>'];
        yield [types::union(types::int, types::string), 'int|string'];
        yield [types::union(types::int, types::union(types::string, types::float)), 'int|string|float'];
        yield [types::union(types::int, types::intersection(types::string, types::float)), 'int|(string&float)'];
        yield [types::intersection(types::int, types::string), 'int&string'];
        yield [types::intersection(types::int, types::intersection(types::string, types::float)), 'int&string&float'];
        yield [types::intersection(types::int, types::union(types::string, types::float)), 'int&(string|float)'];
        yield [types::iterable(), 'iterable'];
        yield [types::iterable(valueType: types::string), 'iterable<string>'];
        yield [types::iterable(types::string, types::int), 'iterable<string, int>'];
        yield [types::callable(), 'callable'];
        yield [types::callable(returnType: types::void), 'callable(): void'];
        yield [types::callable([types::string]), 'callable(string)'];
        yield [types::callable([types::defaultParam(types::string)]), 'callable(string=)'];
        yield [types::callable([types::variadicParam(types::string)]), 'callable(string...)'];
        yield [types::callable([types::variadicParam(types::string)], types::never), 'callable(string...): never'];
        yield [types::closure(), 'Closure'];
        yield [types::closure(returnType: types::void), 'Closure(): void'];
        yield [types::closure([types::string]), 'Closure(string)'];
        yield [types::closure([types::defaultParam(types::string)]), 'Closure(string=)'];
        yield [types::closure([types::variadicParam(types::string)]), 'Closure(string...)'];
        yield [types::closure([types::variadicParam(types::string)], types::never), 'Closure(string...): never'];
        yield [types::classTemplate('T', \ArrayObject::class), 'T:ArrayObject'];
        yield [types::functionTemplate('T', 'strval'), 'T:strval()'];
        yield [types::methodTemplate('T', \ArrayObject::class, 'method'), 'T:ArrayObject::method()'];
        yield [types::literalString, 'literal-string'];
        yield [types::literalInt, 'literal-int'];
        yield [types::int(), 'int'];
        yield [types::classString(types::object), 'class-string<object>'];
        yield [types::classString, 'class-string'];
        yield [types::callableString, 'callable-string'];
        yield [types::interfaceString, 'interface-string'];
        yield [types::enumString, 'enum-string'];
        yield [types::traitString, 'trait-string'];
        yield [types::callableArray, 'callable-array'];
        yield [types::static(\stdClass::class), 'static'];
        yield [types::static(\stdClass::class, types::string, types::int), 'static<string, int>'];
        yield [types::closedResource, 'closed-resource'];
        yield [types::constant('test'), 'test'];
        yield [types::classConstant(\stdClass::class, 'test'), 'stdClass::test'];
        yield [types::keyOf(types::list()), 'key-of<list>'];
        yield [types::valueOf(types::list()), 'value-of<list>'];
    }
}
