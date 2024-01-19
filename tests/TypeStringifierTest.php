<?php

declare(strict_types=1);

namespace Typhoon\TypeStringifier;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(TypeStringifier::class)]
final class TypeStringifierTest extends TestCase
{
    /**
     * @return \Generator<array-key, array{Type, string}>
     */
    public static function typesAndTheirStringRepresentations(): \Generator
    {
        yield [types::never, 'never'];
        yield [types::void, 'void'];
        yield [types::mixed, 'mixed'];
        yield [types::null, 'null'];
        yield [types::true, 'true'];
        yield [types::false, 'false'];
        yield [types::bool, 'bool'];
        yield [types::int, 'int'];
        yield [types::intLiteral(123), '123'];
        yield [types::intLiteral(-123), '-123'];
        yield [types::int(min: 23), 'int<23, max>'];
        yield [types::int(max: 23), 'int<min, 23>'];
        yield [types::int(min: -100, max: 234), 'int<-100, 234>'];
        yield [types::intMask(1, 2, 4), 'int-mask<1, 2, 4>'];
        /** @psalm-suppress InvalidTemplateParam */
        yield [types::intMaskOf(types::classConstant(\RecursiveIteratorIterator::class, 'LEAVES_ONLY')), 'int-mask-of<RecursiveIteratorIterator::LEAVES_ONLY>'];
        yield [types::float, 'float'];
        yield [types::floatLiteral(0.234), '0.234'];
        yield [types::floatLiteral(-0.234), '-0.234'];
        yield [types::numeric, 'numeric'];
        yield [types::arrayKey, 'array-key'];
        yield [types::numericString, 'numeric-string'];
        yield [types::classStringLiteral(\stdClass::class), 'stdClass::class'];
        yield [types::nonEmptyString, 'non-empty-string'];
        yield [types::truthyString, 'truthy-string'];
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
        yield [types::arrayShape(), 'list{}'];
        yield [types::arrayShape(sealed: false), 'array'];
        yield [types::arrayShape([types::int]), 'list{int}'];
        yield [types::arrayShape([types::int, 'a' => types::string]), 'array{0: int, a: string}'];
        yield [types::arrayShape([types::int, 'a' => types::string], sealed: false), 'array{0: int, a: string, ...}'];
        yield [types::arrayShape(['' => types::string]), "array{'': string}"];
        yield [types::arrayShape(['\'' => types::string]), "array{'\\'': string}"];
        yield [types::arrayShape(["\n" => types::string]), "array{'\\n': string}"];
        yield [types::arrayShape([types::int, 'a' => types::string], sealed: false), 'array{0: int, a: string, ...}'];
        yield [types::arrayShape([types::arrayElement(types::int, optional: true)]), 'list{0?: int}'];
        yield [types::arrayShape([types::arrayElement(types::int, optional: true)], sealed: false), 'array{0?: int, ...}'];
        yield [types::arrayShape(['a' => types::arrayElement(types::int, optional: true)]), 'array{a?: int}'];
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
        yield [types::callable([types::param(types::string, hasDefault: true)]), 'callable(string=)'];
        yield [types::callable([types::param(types::string, variadic: true)]), 'callable(string...)'];
        yield [types::callable([types::param(types::string, variadic: true)], types::never), 'callable(string...): never'];
        yield [types::closure(), 'Closure'];
        yield [types::closure(returnType: types::void), 'Closure(): void'];
        yield [types::closure([types::string]), 'Closure(string)'];
        yield [types::closure([types::param(types::string, hasDefault: true)]), 'Closure(string=)'];
        yield [types::closure([types::param(types::string, variadic: true)]), 'Closure(string...)'];
        yield [types::closure([types::param(types::string, variadic: true)], types::never), 'Closure(string...): never'];
        yield [types::template('T'), 'T'];
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
        yield [types::static(), 'static'];
        yield [types::static(types::string, types::int), 'static<string, int>'];
        yield [types::objectShape(), 'object{}'];
        yield [types::objectShape(['name' => types::string, 'obj' => types::object(\stdClass::class)]), 'object{name: string, obj: stdClass}'];
        yield [types::objectShape(['name' => types::prop(types::string, optional: true)]), 'object{name?: string}'];
        yield [types::objectShape(['n\'ame' => types::string]), "object{'n\\'ame': string}"];
        yield [types::objectShape(["\n" => types::string]), "object{'\\n': string}"];
        yield [types::closedResource, 'closed-resource'];
        yield [types::constant('test'), 'test'];
        yield [types::classConstant(\stdClass::class, 'test'), 'stdClass::test'];
        yield [types::keyOf(types::list()), 'key-of<list>'];
        yield [types::valueOf(types::list()), 'value-of<list>'];
        yield [types::conditional(types::arg('a'), is: types::string, if: types::int, else: types::float), '($a is string ? int : float)'];
        yield [types::conditional(types::template('T'), is: types::string, if: types::int, else: types::float), '(T is string ? int : float)'];
    }

    #[DataProvider('typesAndTheirStringRepresentations')]
    public function testItStringifiesTypeCorrectly(Type $type, string $expectedString): void
    {
        $asString = TypeStringifier::stringify($type);

        self::assertSame($expectedString, $asString);
    }
}
