<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @psalm-import-type TemplateReflector from NameAsTypeResolver
 */
#[CoversClass(PhpDocTypeReflector::class)]
final class PhpDocTypeReflectorTest extends TestCase
{
    /**
     * @return \Generator<int, array{string, Type|ReflectionException}>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public static function validTypes(): \Generator
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
        yield ['int-mask', new ReflectionException('int-mask type should have at least 1 argument.')];
        yield ['int-mask<a>', new ReflectionException('Invalid int-mask argument: a.')];
        yield ["int-mask<'a'>", new ReflectionException('Invalid int-mask argument: a.')];
        yield ['int-mask<-1>', new ReflectionException('Invalid int-mask argument: -1.')];
        yield ['int-mask<0>', types::intMask(0)];
        yield ['int-mask<0, 1>', types::intMask(0, 1)];
        yield ['int-mask-of', new ReflectionException('int-mask-of type should have 1 argument, got 0.')];
        yield ['int-mask-of<0, 1>', new ReflectionException('int-mask-of type should have 1 argument, got 2.')];
        /** @psalm-suppress InvalidTemplateParam */
        yield ['int-mask-of<PHP_INT_MAX>', types::intMaskOf(types::constant('PHP_INT_MAX'))];
        yield ['int<0, 1>', types::intRange(0, 1)];
        yield ['int<-10, -23>', types::intRange(-10, -23)];
        yield ['int<min, 123>', types::intRange(max: 123)];
        yield ['int<-99, max>', types::intRange(min: -99)];
        yield ['int<max>', new ReflectionException('int range type should have 2 arguments, got 1.')];
        yield ['int<max, 0>', new ReflectionException('Invalid int range min argument: max.')];
        yield ['int<test, 0>', new ReflectionException('Invalid int range min argument: test.')];
        yield ["int<'test', 0>", new ReflectionException('Invalid int range min argument: test.')];
        yield ['int<0, min>', new ReflectionException('Invalid int range max argument: min.')];
        yield ['int<0, test>', new ReflectionException('Invalid int range max argument: test.')];
        yield ["int<0, 'test'>", new ReflectionException('Invalid int range max argument: test.')];
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
        yield ['\stdClass::class', types::classString(\stdClass::class)];
        yield ['class-string<\stdClass>', types::classString(types::object(\stdClass::class))];
        yield ['float', types::float];
        yield ['literal-string', types::literalString];
        yield ['numeric-string', types::numericString];
        yield ['class-string', types::classString];
        yield ['callable-string', types::callableString];
        yield ['interface-string', types::interfaceString];
        yield ['enum-string', types::enumString];
        yield ['trait-string', types::traitString];
        yield ['non-empty-string', types::nonEmptyString];
        yield ['truthy-string', types::truthyString];
        yield ['non-falsy-string', types::nonFalsyString];
        yield ['string', types::string];
        yield ['numeric', types::numeric];
        yield ['scalar', types::scalar];
        yield ['callable-array', types::callableArray];
        yield ['object', types::object];
        yield ['resource', types::resource];
        yield ['closed-resource', types::closedResource];
        yield ['array-key', types::arrayKey];
        yield ['mixed', types::mixed];
        yield ['list', types::list()];
        yield ['list<mixed>', types::list()];
        yield ['list<int>', types::list(types::int)];
        yield ['list<int, string>', new ReflectionException('list type should have at most 1 argument, got 2.')];
        yield ['non-empty-list<mixed>', types::nonEmptyList()];
        yield ['non-empty-list<int>', types::nonEmptyList(types::int)];
        yield ['non-empty-list<int, string>', new ReflectionException('non-empty-list type should have at most 1 argument, got 2.')];
        yield ['array', types::array()];
        yield ['array<mixed>', types::array()];
        yield ['array<int>', types::array(valueType: types::int)];
        yield ['array<int, string>', types::array(types::int, types::string)];
        yield ['array<int, string, float>', new ReflectionException('array type should have at most 2 arguments, got 3.')];
        yield ['non-empty-array', types::nonEmptyArray()];
        yield ['non-empty-array<mixed>', types::nonEmptyArray()];
        yield ['non-empty-array<int>', types::nonEmptyArray(valueType: types::int)];
        yield ['non-empty-array<int, string>', types::nonEmptyArray(types::int, types::string)];
        yield ['non-empty-array<int, string, float>', new ReflectionException('non-empty-array type should have at most 2 arguments, got 3.')];
        yield ['array{}', types::arrayShape()];
        yield ['array{int}', types::arrayShape([types::int])];
        yield ['array{int, 1?: string}', types::arrayShape([types::int, 1 => types::arrayElement(types::string, true)])];
        yield ['array{int, a: string}', types::arrayShape([types::int, 'a' => types::string])];
        yield ['array{a: int}', types::arrayShape(['a' => types::int])];
        yield ['array{a?: int}', types::arrayShape(['a' => types::arrayElement(types::int, true)])];
        yield ['array{a: int, ...}', types::arrayShape(['a' => types::int], sealed: false)];
        yield ['array{...}', types::arrayShape(sealed: false)];
        yield ['list{}', types::arrayShape()];
        yield ['list{int}', types::arrayShape([types::int])];
        yield ['list{int, 1?: string}', types::arrayShape([types::int, 1 => types::arrayElement(types::string, true)])];
        yield ['list{...}', types::arrayShape(sealed: false)];
        yield ['iterable', types::iterable()];
        yield ['iterable<mixed>', types::iterable()];
        yield ['iterable<int>', types::iterable(valueType: types::int)];
        yield ['iterable<int, string>', types::iterable(types::int, types::string)];
        yield ['iterable<object, string>', types::iterable(types::object, types::string)];
        yield ['iterable<int, string, float>', new ReflectionException('iterable type should have at most 2 arguments, got 3.')];
        yield ['string[]', types::array(valueType: types::string)];
        yield ['\stdClass', types::object(\stdClass::class)];
        yield ['\Traversable', types::object(\Traversable::class)];
        yield ['\stdClass<int, string>', types::object(\stdClass::class, types::int, types::string)];
        yield ['static', new ReflectionException('Neither class "static", nor constant "static" exist.')];
        yield ['static<int, string>', new ReflectionException('Neither class "static", nor constant "static" exist.')];
        yield ['object{}', types::objectShape()];
        yield ['object{a: int}', types::objectShape(['a' => types::int])];
        yield ['object{a?: int}', types::objectShape(['a' => types::prop(types::int, true)])];
        yield ['PHP_INT_MAX', types::constant('PHP_INT_MAX')];
        yield ['\stdClass::C', types::classConstant(\stdClass::class, 'C')];
        yield ['key-of<array>', types::keyOf(types::array())];
        yield ['key-of', new ReflectionException('key-of type should have 1 argument, got 0.')];
        yield ['key-of<array, array>', new ReflectionException('key-of type should have 1 argument, got 2.')];
        yield ['value-of<array>', types::valueOf(types::array())];
        yield ['value-of', new ReflectionException('value-of type should have 1 argument, got 0.')];
        yield ['value-of<array, array>', new ReflectionException('value-of type should have 1 argument, got 2.')];
        yield ['\Traversable&\Countable', types::intersection(types::object(\Traversable::class), types::object(\Countable::class))];
        yield ['string|int', types::union(types::string, types::int)];
        yield ['callable', types::callable()];
        yield ['callable(): mixed', types::callable(returnType: types::mixed)];
        yield ['callable(): void', types::callable(returnType: types::void)];
        yield ['callable(string, int): void', types::callable([types::string, types::int], returnType: types::void)];
        yield ['callable(string=, int): void', types::callable([types::param(types::string, true), types::int], returnType: types::void)];
        yield ['callable(string=, int...): void', types::callable([types::param(types::string, true), types::param(types::int, variadic: true)], returnType: types::void)];
        yield ['\Closure', types::closure()];
        yield ['\Closure(): mixed', types::closure(returnType: types::mixed)];
        yield ['\Closure(): void', types::closure(returnType: types::void)];
        yield ['\Closure(string, int): void', types::closure([types::string, types::int], returnType: types::void)];
        yield ['\Closure(string=, int): void', types::closure([types::param(types::string, true), types::int], returnType: types::void)];
        yield ['\Closure(string=, int...): void', types::closure([types::param(types::string, true), types::param(types::int, variadic: true)], returnType: types::void)];
        yield ['($arg is true ? string : null)', types::conditional(types::arg('arg'), types::true, types::string, types::null)];
        yield ['($arg is not true ? null : string)', types::conditional(types::arg('arg'), types::true, types::string, types::null)];
        yield ['(int is not true ? null : string)', new ReflectionException('Conditional type subject should be an argument or a template, got int.')];
    }

    #[DataProvider('validTypes')]
    public function testValidTypes(string $phpDocStringType, Type|ReflectionException $expectedTypeOrException): void
    {
        $parser = new PhpDocParser();
        $phpDocType = $parser->parsePhpDoc("/** @var {$phpDocStringType} */")->varType();

        self::assertNotNull($phpDocType);

        try {
            /** @var NameContext<TemplateReflector> */
            $nameContext = new NameContext();
            $type = PhpDocTypeReflector::reflect(
                nameContext: $nameContext,
                classExistenceChecker: new NativeClassExistenceChecker(),
                typeNode: $phpDocType,
            );
        } catch (\Throwable $exception) {
            self::assertEquals($expectedTypeOrException, $exception);

            return;
        }

        self::assertEquals($expectedTypeOrException, $type);
    }
}
