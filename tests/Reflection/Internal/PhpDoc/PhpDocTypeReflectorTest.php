<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(PhpDocTypeReflector::class)]
final class PhpDocTypeReflectorTest extends TestCase
{
    /**
     * @return \Generator<list{0: string, 1: Type|InvalidPhpDocType, 2?: Context}>
     */
    private static function validTypes(): \Generator
    {
        yield ['never', types::never];
        yield ['void', types::void];
        yield ['null', types::null];
        yield ['false', types::false];
        yield ['true', types::true];
        yield ['bool', types::bool];
        yield ['boolean', types::bool];
        yield ['literal-int', types::literalInt];
        yield ['int', types::int];
        yield ['integer', types::int];
        yield ['?int', types::nullable(types::int)];
        yield ['positive-int', types::positiveInt];
        yield ['negative-int', types::negativeInt];
        yield ['non-positive-int', types::nonPositiveInt];
        yield ['non-negative-int', types::nonNegativeInt];
        yield ['non-zero-int', types::nonZeroInt];
        yield ['int-mask', types::intMaskOf(types::never)];
        yield ['int-mask<1>', types::intMask(1)];
        yield ['int-mask<1|2>', types::intMask(1, 2)];
        yield ['int-mask-of<stdClass::CON_*>', types::intMaskOf(types::classConstantMask(\stdClass::class, 'CON_'))];
        yield ['int<0, 1>', types::intRange(0, 1)];
        yield ['int<-10, -23>', types::intRange(-10, -23)];
        yield ['int<min, 123>', types::intRange(max: 123)];
        yield ['int<-99, max>', types::intRange(min: -99)];
        yield ['int<min, max>', types::int];
        yield ['0', types::int(0)];
        yield ['932', types::int(932)];
        yield ['-5', types::int(-5)];
        yield ['0.5', types::float(0.5)];
        yield ['-4.67', types::float(-4.67)];
        yield ['"0"', types::string('0')];
        yield ["'0'", types::string('0')];
        yield ['"str"', types::string('str')];
        yield ["'str'", types::string('str')];
        yield ["'\\n'", types::string('\n')];
        yield ['\stdClass::class', types::class(\stdClass::class)];
        yield ['class-string<\stdClass>', types::classString(types::object(\stdClass::class))];
        yield ['float', types::float];
        yield ['double', types::float];
        yield ['float<10.0002, 231.00002>', types::floatRange(10.0002, 231.00002)];
        yield ['float<min, 123>', types::floatRange(max: 123)];
        yield ['float<-99, max>', types::floatRange(min: -99)];
        yield ['literal-string', types::literalString];
        yield ['literal-float', types::literalFloat];
        yield ['numeric-string', types::numericString];
        yield ['class-string', types::classString];
        yield ['callable-string', types::callableString()];
        yield ['interface-string', types::classString];
        yield ['enum-string', types::classString];
        yield ['trait-string', types::classString];
        yield ['non-empty-string', types::nonEmptyString];
        yield ['truthy-string', types::truthyString];
        yield ['non-falsy-string', types::truthyString];
        yield ['string', types::string];
        yield ['numeric', types::numeric];
        yield ['scalar', types::scalar];
        yield ['callable-array', types::callableArray()];
        yield ['object', types::object];
        yield ['resource', types::resource];
        yield ['closed-resource', types::resource];
        yield ['open-resource', types::resource];
        yield ['array-key', types::arrayKey];
        yield ['mixed', types::mixed];
        yield ['list', types::list()];
        yield ['list<mixed>', types::list()];
        yield ['list<int>', types::list(types::int)];
        yield ['list<int, string>', new InvalidPhpDocType('list type should have at most 1 type argument, got 2')];
        yield ['non-empty-list', types::nonEmptyList()];
        yield ['non-empty-list<mixed>', types::nonEmptyList()];
        yield ['non-empty-list<int>', types::nonEmptyList(types::int)];
        yield ['non-empty-list<int, string>', new InvalidPhpDocType('list type should have at most 1 type argument, got 2')];
        yield ['array', types::array()];
        yield ['array<mixed>', types::array()];
        yield ['array<int>', types::array(value: types::int)];
        yield ['array<int, string>', types::array(types::int, types::string)];
        yield ['array<int, string, float>', new InvalidPhpDocType('array type should have at most 2 type arguments, got 3')];
        yield ['non-empty-array', types::nonEmptyArray()];
        yield ['non-empty-array<mixed>', types::nonEmptyArray()];
        yield ['non-empty-array<int>', types::nonEmptyArray(value: types::int)];
        yield ['non-empty-array<int, string>', types::nonEmptyArray(types::int, types::string)];
        yield ['non-empty-array<int, string, float>', new InvalidPhpDocType('array type should have at most 2 type arguments, got 3')];
        yield ['array{}', types::arrayShape()];
        yield ['array{int}', types::arrayShape([types::int])];
        yield ['array{int, 1?: string}', types::arrayShape([types::int, 1 => types::optional(types::string)])];
        yield ['array{int, a: string}', types::arrayShape([types::int, 'a' => types::string])];
        yield ['array{a: int}', types::arrayShape(['a' => types::int])];
        yield ['array{a?: int}', types::arrayShape(['a' => types::optional(types::int)])];
        yield ['array{a: int, ...}', types::unsealedArrayShape(['a' => types::int], value: types::mixed)];
        yield ['array{...}', types::unsealedArrayShape(value: types::mixed)];
        yield ['list{}', types::listShape()];
        yield ['list{int}', types::listShape([types::int])];
        yield ['list{int, 1?: string}', types::listShape([types::int, 1 => types::optional(types::string)])];
        yield ['list{...}', types::unsealedListShape(value: types::mixed)];
        yield ['iterable', types::iterable()];
        yield ['iterable<mixed>', types::iterable()];
        yield ['iterable<int>', types::iterable(value: types::int)];
        yield ['iterable<int, string>', types::iterable(types::int, types::string)];
        yield ['iterable<object, string>', types::iterable(types::object, types::string)];
        yield ['iterable<int, string, float>', new InvalidPhpDocType('iterable type should have at most 2 type arguments, got 3')];
        yield ['string[]', types::array(value: types::string)];
        yield ['stdClass', types::object(\stdClass::class)];
        yield ['Traversable', types::object(\Traversable::class)];
        yield ['Traversable<string>', types::object(\Traversable::class, [types::mixed, types::string])];
        yield ['Traversable<int, int, int>', new InvalidPhpDocType('Traversable type should have at most 2 type arguments, got 3')];
        yield ['Iterator<string>', types::object(\Iterator::class, [types::mixed, types::string])];
        yield ['Iterator<int, int, int>', new InvalidPhpDocType('Iterator type should have at most 2 type arguments, got 3')];
        yield ['IteratorAggregate<string>', types::object(\IteratorAggregate::class, [types::mixed, types::string])];
        yield ['IteratorAggregate<int, int, int>', new InvalidPhpDocType('IteratorAggregate type should have at most 2 type arguments, got 3')];
        yield ['Generator', types::Generator()];
        yield ['Generator<string>', types::Generator(value: types::string)];
        yield ['Generator<int, int, int, int, int>', new InvalidPhpDocType('Generator type should have at most 4 type arguments, got 5')];
        yield ['stdClass<int, string>', types::object(\stdClass::class, [types::int, types::string])];
        yield ['object{}', types::objectShape()];
        yield ['object{a: int}', types::objectShape(['a' => types::int])];
        yield ['object{a?: int}', types::objectShape(['a' => types::optional(types::int)])];
        yield ['stdClass::C', types::classConstant(types::object(\stdClass::class), 'C')];
        yield ['stdClass::*', types::classConstantMask(types::object(\stdClass::class))];
        yield ['stdClass::C_*', types::classConstantMask(types::object(\stdClass::class), 'C_')];
        yield ['key-of<array>', types::keyOf(types::array())];
        yield ['key-of', new InvalidPhpDocType('key-of type should have 1 type argument, got 0')];
        yield ['key-of<array, array>', new InvalidPhpDocType('key-of type should have 1 type argument, got 2')];
        yield ['value-of<array>', types::valueOf(types::array())];
        yield ['value-of', new InvalidPhpDocType('value-of type should have 1 type argument, got 0')];
        yield ['value-of<array, array>', new InvalidPhpDocType('value-of type should have 1 type argument, got 2')];
        yield ['Traversable&\Countable', types::intersection(types::object(\Traversable::class), types::object(\Countable::class))];
        yield ['string|int', types::union(types::string, types::int)];
        yield ['callable', types::callable()];
        yield ['callable(): mixed', types::callable(return: types::mixed)];
        yield ['callable(): void', types::callable(return: types::void)];
        yield ['callable(string, int): void', types::callable([types::string, types::int], return: types::void)];
        yield ['callable(string=, int): void', types::callable([types::param(types::string, true), types::int], return: types::void)];
        yield ['callable(string=, int...): void', types::callable([types::param(types::string, true), types::param(types::int, variadic: true)], return: types::void)];
        yield ['Closure', types::Closure()];
        yield ['Closure(): mixed', types::Closure(return: types::mixed)];
        yield ['Closure(): void', types::Closure(return: types::void)];
        yield ['Closure(string, int): void', types::Closure([types::string, types::int], return: types::void)];
        yield ['Closure(string=, int): void', types::Closure([types::param(types::string, true), types::int], return: types::void)];
        yield ['Closure(string=, int...): void', types::Closure([types::param(types::string, true), types::param(types::int, variadic: true)], return: types::void)];
        yield ['self', types::self()];
        yield ['self<int, string>', types::self([types::int, types::string])];
        yield ['parent', types::parent()];
        yield ['parent<int, string>', types::parent([types::int, types::string])];
        yield ['static', types::static()];
        yield ['static<int, string>', types::static([types::int, types::string])];
        yield ['T[K]', types::offset(types::object('T'), types::object('K'))];
        yield [
            '($return is true ? string : void)',
            types::conditional(types::functionArg('var_export', 'return'), types::true, types::string, types::void),
            Context::start('')->enterFunction('var_export'),
        ];
        yield [
            '($return is not true ? void : string)',
            types::conditional(types::functionArg('var_export', 'return'), types::true, types::string, types::void),
            Context::start('')->enterFunction('var_export'),
        ];
        yield [
            '(T is true ? string : void)',
            types::conditional(types::functionTemplate('x', 'T'), types::true, types::string, types::void),
            Context::start('')->enterFunction('x', templateNames: ['T']),
        ];
    }

    /**
     * @return \Generator<string, list{string, Type|InvalidPhpDocType, Context}>
     */
    public static function validTypesNamed(): \Generator
    {
        $defaultContext = Context::start('');

        foreach (self::validTypes() as $args) {
            yield $args[0] => [$args[0], $args[1], $args[2] ?? $defaultContext];
        }
    }

    #[DataProvider('validTypesNamed')]
    public function testValidTypes(string $phpDoc, Type|InvalidPhpDocType $expected, Context $context): void
    {
        $parser = new PhpDocParser();
        $phpDocType = $parser->parse("/** @var {$phpDoc} */")->varType();
        $reflector = new PhpDocTypeReflector($context);

        if ($expected instanceof InvalidPhpDocType) {
            $this->expectExceptionObject($expected);
        }

        $type = $reflector->reflectType($phpDocType);

        self::assertEquals($expected, $type);
    }

    public function testItReturnsNullTypeIfNullNodePassed(): void
    {
        $reflector = new PhpDocTypeReflector(Context::start(''));

        $result = $reflector->reflectType(null);

        self::assertNull($result);
    }
}
