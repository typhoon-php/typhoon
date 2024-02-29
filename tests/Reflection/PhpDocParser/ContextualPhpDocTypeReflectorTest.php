<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Type\Type;
use Typhoon\Type\types;

#[CoversClass(ContextualPhpDocTypeReflector::class)]
final class ContextualPhpDocTypeReflectorTest extends TestCase
{
    /**
     * @return \Generator<string, array{string, Type|\Throwable}>
     */
    public static function validTypesNamed(): \Generator
    {
        foreach (self::validTypes() as [$phpDocStringType, $expectedTypeOrException]) {
            yield $phpDocStringType => [$phpDocStringType, $expectedTypeOrException];
        }
    }

    /**
     * @return \Generator<array{string, Type|\Throwable}>
     */
    private static function validTypes(): \Generator
    {
        yield ['never', types::never];
        yield ['void', types::void];
        yield ['null', types::null];
        yield ['false', types::false];
        yield ['true', types::true];
        yield ['bool', types::bool];
        yield ['literal-int', types::literalInt];
        yield ['int', types::int];
        yield ['?int', types::nullable(types::int)];
        yield ['positive-int', types::positiveInt];
        yield ['negative-int', types::negativeInt];
        yield ['non-positive-int', types::nonPositiveInt];
        yield ['non-negative-int', types::nonNegativeInt];
        yield ['int-mask', types::intMask(types::never)];
        yield ['int-mask<0>', types::intMask(types::literalValue(0))];
        yield ['int-mask<0, 1>', types::intMask(types::union(types::literalValue(0), types::literalValue(1)))];
        yield ['int-mask-of', types::intMask(types::never)];
        yield ['int-mask-of<PHP_INT_MAX>', types::intMask(types::constant('PHP_INT_MAX'))];
        yield ['int<0, 1>', types::intRange(0, 1)];
        yield ['int<-10, -23>', types::intRange(-10, -23)];
        yield ['int<min, 123>', types::intRange(max: 123)];
        yield ['int<-99, max>', types::intRange(min: -99)];
        yield ['int<max>', new DefaultReflectionException('int range type should have 2 arguments, got 1.')];
        yield ['int<max, 0>', new DefaultReflectionException('Invalid int range min argument: max.')];
        yield ['int<test, 0>', new DefaultReflectionException('Invalid int range min argument: test.')];
        yield ["int<'test', 0>", new DefaultReflectionException('Invalid int range min argument: test.')];
        yield ['int<0, min>', new DefaultReflectionException('Invalid int range max argument: min.')];
        yield ['int<0, test>', new DefaultReflectionException('Invalid int range max argument: test.')];
        yield ["int<0, 'test'>", new DefaultReflectionException('Invalid int range max argument: test.')];
        yield ['int<min, max>', types::int];
        yield ['0', types::literalValue(0)];
        yield ['932', types::literalValue(932)];
        yield ['-5', types::literalValue(-5)];
        yield ['0.5', types::literalValue(0.5)];
        yield ['-4.67', types::literalValue(-4.67)];
        yield ['"0"', types::literalValue('0')];
        yield ["'0'", types::literalValue('0')];
        yield ['"str"', types::literalValue('str')];
        yield ["'str'", types::literalValue('str')];
        yield ["'\\n'", types::literalValue('\n')];
        yield ['\stdClass::class', types::classString(types::object(\stdClass::class))];
        yield ['class-string<\stdClass>', types::classString(types::object(\stdClass::class))];
        yield ['float', types::float];
        yield ['literal-string', types::literalString];
        yield ['numeric-string', types::numericString];
        yield ['class-string', types::classString];
        yield ['callable-string', types::intersection(types::callable(), types::string)];
        yield ['interface-string', types::classString];
        yield ['enum-string', types::classString];
        yield ['trait-string', types::classString];
        yield ['non-empty-string', types::nonEmptyString];
        yield ['truthy-string', types::truthyString];
        yield ['non-falsy-string', types::truthyString];
        yield ['string', types::string];
        yield ['numeric', types::numeric];
        yield ['scalar', types::scalar];
        yield ['callable-array', types::intersection(types::callable(), types::array())];
        yield ['object', types::object];
        yield ['resource', types::resource];
        yield ['closed-resource', types::resource];
        yield ['open-resource', types::resource];
        yield ['array-key', types::arrayKey];
        yield ['mixed', types::mixed];
        yield ['list', types::list()];
        yield ['list<mixed>', types::list()];
        yield ['list<int>', types::list(types::int)];
        yield ['list<int, string>', new DefaultReflectionException('list type should have at most 1 argument, got 2.')];
        yield ['non-empty-list<mixed>', types::nonEmptyList()];
        yield ['non-empty-list<int>', types::nonEmptyList(types::int)];
        yield ['non-empty-list<int, string>', new DefaultReflectionException('list type should have at most 1 argument, got 2.')];
        yield ['array', types::array()];
        yield ['array<mixed>', types::array()];
        yield ['array<int>', types::array(value: types::int)];
        yield ['array<int, string>', types::array(types::int, types::string)];
        yield ['array<int, string, float>', new DefaultReflectionException('array type should have at most 2 arguments, got 3.')];
        yield ['non-empty-array', types::nonEmptyArray()];
        yield ['non-empty-array<mixed>', types::nonEmptyArray()];
        yield ['non-empty-array<int>', types::nonEmptyArray(value: types::int)];
        yield ['non-empty-array<int, string>', types::nonEmptyArray(types::int, types::string)];
        yield ['non-empty-array<int, string, float>', new DefaultReflectionException('array type should have at most 2 arguments, got 3.')];
        yield ['array{}', types::arrayShape()];
        yield ['array{int}', types::arrayShape([types::int])];
        yield ['array{int, 1?: string}', types::arrayShape([types::int, 1 => types::arrayElement(types::string, true)])];
        yield ['array{int, a: string}', types::arrayShape([types::int, 'a' => types::string])];
        yield ['array{a: int}', types::arrayShape(['a' => types::int])];
        yield ['array{a?: int}', types::arrayShape(['a' => types::arrayElement(types::int, true)])];
        yield ['array{a: int, ...}', types::arrayShape(['a' => types::int], value: types::mixed)];
        yield ['array{...}', types::arrayShape(value: types::mixed)];
        yield ['list{}', types::arrayShape()];
        yield ['list{int}', types::arrayShape([types::int])];
        yield ['list{int, 1?: string}', types::arrayShape([types::int, 1 => types::arrayElement(types::string, true)])];
        yield ['list{...}', types::arrayShape(value: types::mixed)];
        yield ['iterable', types::iterable()];
        yield ['iterable<mixed>', types::iterable()];
        yield ['iterable<int>', types::iterable(value: types::int)];
        yield ['iterable<int, string>', types::iterable(types::int, types::string)];
        yield ['iterable<object, string>', types::iterable(types::object, types::string)];
        yield ['iterable<int, string, float>', new DefaultReflectionException('iterable type should have at most 2 arguments, got 3.')];
        yield ['string[]', types::array(value: types::string)];
        yield ['\stdClass', types::object(\stdClass::class)];
        yield ['\Traversable', types::object(\Traversable::class)];
        yield ['\stdClass<int, string>', types::object(\stdClass::class, types::int, types::string)];
        yield ['static', new \InvalidArgumentException('static cannot be used outside of the class scope.')];
        yield ['static<int, string>', new \InvalidArgumentException('static cannot be used outside of the class scope.')];
        yield ['object{}', types::objectShape()];
        yield ['object{a: int}', types::objectShape(['a' => types::int])];
        yield ['object{a?: int}', types::objectShape(['a' => types::prop(types::int, true)])];
        yield ['PHP_INT_MAX', types::constant('PHP_INT_MAX')];
        yield ['\stdClass::C', types::classConstant(types::object(\stdClass::class), 'C')];
        yield ['key-of<array>', types::key(types::array())];
        yield ['key-of', new DefaultReflectionException('key-of type should have 1 argument, got 0.')];
        yield ['key-of<array, array>', new DefaultReflectionException('key-of type should have 1 argument, got 2.')];
        yield ['value-of<array>', types::value(types::array())];
        yield ['value-of', new DefaultReflectionException('value-of type should have 1 argument, got 0.')];
        yield ['value-of<array, array>', new DefaultReflectionException('value-of type should have 1 argument, got 2.')];
        yield ['\Traversable&\Countable', types::intersection(types::object(\Traversable::class), types::object(\Countable::class))];
        yield ['string|int', types::union(types::string, types::int)];
        yield ['callable', types::callable()];
        yield ['callable(): mixed', types::callable(return: types::mixed)];
        yield ['callable(): void', types::callable(return: types::void)];
        yield ['callable(string, int): void', types::callable([types::string, types::int], return: types::void)];
        yield ['callable(string=, int): void', types::callable([types::param(types::string, true), types::int], return: types::void)];
        yield ['callable(string=, int...): void', types::callable([types::param(types::string, true), types::param(types::int, variadic: true)], return: types::void)];
        yield ['\Closure', types::closure()];
        yield ['\Closure(): mixed', types::closure(return: types::mixed)];
        yield ['\Closure(): void', types::closure(return: types::void)];
        yield ['\Closure(string, int): void', types::closure([types::string, types::int], return: types::void)];
        yield ['\Closure(string=, int): void', types::closure([types::param(types::string, true), types::int], return: types::void)];
        yield ['\Closure(string=, int...): void', types::closure([types::param(types::string, true), types::param(types::int, variadic: true)], return: types::void)];
        yield ['($arg is true ? string : null)', types::conditional(types::arg('arg'), types::true, types::string, types::null)];
        yield ['($arg is not true ? null : string)', types::conditional(types::arg('arg'), types::true, types::string, types::null)];
    }

    #[DataProvider('validTypesNamed')]
    public function testValidTypes(string $phpDocStringType, Type|\Throwable $expectedTypeOrException): void
    {
        $parser = new PhpDocParser();
        $phpDocType = $parser->parsePhpDoc("/** @var {$phpDocStringType} */")->varType();
        \assert($phpDocType !== null);

        try {
            $type = (new ContextualPhpDocTypeReflector())->reflect($phpDocType);
        } catch (\Throwable $exception) {
            self::assertEquals($expectedTypeOrException, $exception);

            return;
        }

        self::assertEquals($expectedTypeOrException, $type);
    }
}
