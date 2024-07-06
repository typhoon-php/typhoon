<?php

declare(strict_types=1);

namespace Typhoon\TypeStringifier;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Variance;

#[CoversClass(TypeStringifier::class)]
#[CoversFunction('Typhoon\TypeStringifier\stringify')]
final class TypeStringifierTest extends TestCase
{
    /**
     * @return \Generator<array-key, array{Type, string}>
     */
    public static function types(): \Generator
    {
        yield [types::never, 'never'];
        yield [types::void, 'void'];
        yield [types::mixed, 'mixed'];
        yield [types::null, 'null'];
        yield [types::true, 'true'];
        yield [types::false, 'false'];
        yield [types::bool, 'bool'];
        yield [types::int, 'int'];
        yield [types::intValue(123), '123'];
        yield [types::intValue(-123), '-123'];
        yield [types::int(), 'int'];
        yield [types::int(min: 23), 'int<23, max>'];
        yield [types::int(max: 23), 'int<min, 23>'];
        yield [types::int(min: -100, max: 234), 'int<-100, 234>'];
        yield [types::intMask(types::union(types::intValue(1), types::intValue(2), types::intValue(4))), 'int-mask-of<1|2|4>'];
        yield [types::intMask(types::classConstant(\RecursiveIteratorIterator::class, 'LEAVES_ONLY')), 'int-mask-of<RecursiveIteratorIterator::LEAVES_ONLY>'];
        yield [types::float, 'float'];
        yield [types::floatValue(0.234), '0.234'];
        yield [types::floatValue(-0.234), '-0.234'];
        yield [types::numeric, 'numeric'];
        yield [types::arrayKey, 'int|string'];
        yield [types::numericString, 'numeric-string'];
        yield [types::nonEmptyString, 'non-empty-string'];
        yield [types::truthyString, 'truthy-string'];
        yield [types::string, 'string'];
        yield [types::stringValue('abcd'), "'abcd'"];
        yield [types::stringValue("a'bcd"), "'a\\'bcd'"];
        yield [types::stringValue("a\\\\'bcd"), "'a\\\\\\\\\\'bcd'"];
        yield [types::stringValue("\n"), "'\\n'"];
        yield [types::lowercaseString, 'lowercase-string'];
        yield [types::scalar, 'scalar'];
        yield [types::union(types::bool, types::int, types::float, types::string), 'scalar'];
        yield [types::resource, 'resource'];
        yield [types::nonEmptyList(), 'list{mixed, ...}'];
        yield [types::nonEmptyList(types::string), 'list{string, ...<string>}'];
        yield [types::list(), 'list'];
        yield [types::list(types::string), 'list<string>'];
        yield [types::listShape(), 'list{}'];
        yield [types::listShape(value: types::mixed), 'list'];
        yield [types::listShape([types::int]), 'list{int}'];
        yield [types::listShape([types::int, 2 => types::string]), 'list{0: int, 2: string}'];
        yield [types::listShape([types::int, 3 => types::string], value: types::mixed), 'list{0: int, 3: string, ...}'];
        yield [types::listShape([types::arrayElement(types::int, optional: true)]), 'list{0?: int}'];
        yield [types::listShape([types::arrayElement(types::int, optional: true)], value: types::mixed), 'list{0?: int, ...}'];
        yield [types::listShape([3 => types::arrayElement(types::int, optional: true)]), 'list{3?: int}'];
        yield [types::listShape([4 => types::float], value: types::string), 'list{4: float, ...<string>}'];
        yield [types::nonEmptyArray(), 'non-empty-array'];
        yield [types::nonEmptyArray(value: types::string), 'non-empty-array<string>'];
        yield [types::nonEmptyArray(types::string, types::int), 'non-empty-array<string, int>'];
        yield [types::array, 'array'];
        yield [types::array(types::nonEmptyString), 'array<non-empty-string, mixed>'];
        yield [types::array(value: types::string), 'array<string>'];
        yield [types::array(types::string, types::int), 'array<string, int>'];
        yield [types::arrayShape(), 'array{}'];
        yield [types::arrayShape(value: types::mixed), 'array'];
        yield [types::arrayShape([types::int]), 'array{int}'];
        yield [types::arrayShape([types::int, 'a' => types::string]), 'array{0: int, a: string}'];
        yield [types::arrayShape([types::int, 'a' => types::string], value: types::mixed), 'array{0: int, a: string, ...}'];
        yield [types::arrayShape(['' => types::string]), "array{'': string}"];
        yield [types::arrayShape(['\'' => types::string]), "array{'\\'': string}"];
        yield [types::arrayShape(["\n" => types::string]), "array{'\\n': string}"];
        yield [types::arrayShape([types::int, 'a' => types::string], value: types::mixed), 'array{0: int, a: string, ...}'];
        yield [types::arrayShape([types::arrayElement(types::int, optional: true)]), 'array{0?: int}'];
        yield [types::arrayShape([types::arrayElement(types::int, optional: true)], value: types::mixed), 'array{0?: int, ...}'];
        yield [types::arrayShape(['a' => types::arrayElement(types::int, optional: true)]), 'array{a?: int}'];
        yield [types::arrayShape(['a' => types::float], key: types::int, value: types::string), 'array{a: float, ...<int, string>}'];
        yield [types::object, 'object'];
        yield [types::object(\ArrayObject::class), 'ArrayObject'];
        yield [types::object(\ArrayObject::class, types::arrayKey, types::string), 'ArrayObject<int|string, string>'];
        yield [types::union(types::int, types::string), 'int|string'];
        yield [types::union(types::int, types::union(types::string, types::float)), 'int|string|float'];
        yield [types::union(types::int, types::intersection(types::string, types::float)), 'int|(string&float)'];
        yield [types::intersection(types::int, types::string), 'int&string'];
        yield [types::intersection(types::int, types::intersection(types::string, types::float)), 'int&string&float'];
        yield [types::intersection(types::int, types::union(types::string, types::float)), 'int&(string|float)'];
        yield [types::iterable(), 'iterable'];
        yield [types::iterable(value: types::string), 'iterable<string>'];
        yield [types::iterable(types::string, types::int), 'iterable<string, int>'];
        yield [types::callable(), 'callable'];
        yield [types::callable(return: types::void), 'callable(): void'];
        yield [types::callable([types::string]), 'callable(string): mixed'];
        yield [types::callable([types::param(types::string, hasDefault: true)]), 'callable(string=): mixed'];
        yield [types::callable([types::param(types::string, variadic: true)]), 'callable(string...): mixed'];
        yield [types::callable([types::param(types::string, variadic: true)], types::never), 'callable(string...): never'];
        yield [types::closure(), 'Closure'];
        yield [types::closure(return: types::void), 'Closure(): void'];
        yield [types::closure([types::string]), 'Closure(string): mixed'];
        yield [types::closure([types::param(types::string, hasDefault: true)]), 'Closure(string=): mixed'];
        yield [types::closure([types::param(types::string, variadic: true)]), 'Closure(string...): mixed'];
        yield [types::closure([types::param(types::string, variadic: true)], types::never), 'Closure(string...): never'];
        yield [types::functionTemplate('trim', 'T'), 'T#trim()'];
        yield [types::classTemplate(\stdClass::class, 'T'), 'T#stdClass'];
        yield [types::classTemplate(DeclarationId::anonymousClass('file', 1, 13), 'T'), 'T#anon.class:file:1:13'];
        yield [types::methodTemplate(\stdClass::class, 'm', 'T'), 'T#stdClass::m()'];
        yield [types::literalString, 'literal-string'];
        yield [types::literalInt, 'literal-int'];
        yield [types::classString(types::classTemplate(\stdClass::class, 'T')), 'class-string<T#stdClass>'];
        yield [types::classString, 'class-string'];
        yield [types::objectShape(), 'object'];
        yield [types::objectShape(['name' => types::string, 'obj' => types::object(\stdClass::class)]), 'object{name: string, obj: stdClass}'];
        yield [types::objectShape(['name' => types::prop(types::string, optional: true)]), 'object{name?: string}'];
        yield [types::objectShape(['n\'ame' => types::string]), "object{'n\\'ame': string}"];
        yield [types::objectShape(["\n" => types::string]), "object{'\\n': string}"];
        yield [types::constant('test'), 'const<test>'];
        yield [types::classConstant(\stdClass::class, 'test'), 'stdClass::test'];
        yield [types::key(types::list()), 'key-of<list>'];
        yield [types::value(types::list()), 'list[key-of<list>]'];
        yield [types::conditional(types::arg('a'), if: types::string, then: types::int, else: types::float), '($a is string ? int : float)'];
        yield [types::conditional(types::functionTemplate('trim', 'T'), if: types::string, then: types::int, else: types::float), '(T#trim() is string ? int : float)'];
        yield [types::array(value: types::varianceAware(types::int, Variance::Covariant)), 'array<covariant int>'];
        yield [types::offset(types::classTemplate('A', 'T'), types::stringValue('abc')), "T#A['abc']"];
        yield [types::alias(DeclarationId::alias('Some', 'A')), 'A@Some'];
        yield [types::static(), 'static'];
        yield [types::static('X\Y'), 'static@X\Y'];
        yield [types::static('X\Y', types::string), 'static@X\Y<string>'];
    }

    #[DataProvider('types')]
    public function test(Type $type, string $expectedString): void
    {
        $typeAsString = stringify($type);

        self::assertSame($expectedString, $typeAsString);
    }
}
