<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StaticResolver::class)]
final class StaticResolverTest extends TestCase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return \Generator<array{Type, Type}>
     */
    public static function types(): \Generator
    {
        yield [types::never, types::never];
        yield [types::void, types::void];
        yield [types::null, types::null];
        yield [types::false, types::false];
        yield [types::true, types::true];
        yield [types::bool, types::bool];
        yield [types::literalInt, types::literalInt];
        yield [types::int, types::int];
        yield [types::float, types::float];
        yield [types::literalString, types::literalString];
        yield [types::numericString, types::numericString];
        yield [types::classString, types::classString];
        yield [types::callableString, types::callableString];
        yield [types::interfaceString, types::interfaceString];
        yield [types::enumString, types::enumString];
        yield [types::traitString, types::traitString];
        yield [types::nonEmptyString, types::nonEmptyString];
        yield [types::string, types::string];
        yield [types::numeric, types::numeric];
        yield [types::scalar, types::scalar];
        yield [types::callableArray, types::callableArray];
        yield [types::object, types::object];
        yield [types::resource, types::resource];
        yield [types::closedResource, types::closedResource];
        yield [types::arrayKey, types::arrayKey];
        yield [types::mixed, types::mixed];
        yield [types::int(-10, 10), types::int(-10, 10)];
        yield [types::intLiteral(123), types::intLiteral(123)];
        yield [types::floatLiteral(-2.311), types::floatLiteral(-2.311)];
        yield [types::stringLiteral('abc'), types::stringLiteral('abc')];
        yield [types::constant('FOO'), types::constant('FOO')];
        yield [types::classConstant(\stdClass::class, 'FOO'), types::classConstant(\stdClass::class, 'FOO')];
        yield [types::classTemplate(\stdClass::class, 'T'), types::classTemplate(\stdClass::class, 'T')];
        yield [types::methodTemplate(\stdClass::class, 'm', 'T'), types::methodTemplate(\stdClass::class, 'm', 'T')];
        yield [types::functionTemplate('trim', 'T'), types::functionTemplate('trim', 'T')];

        $static = types::static(self::class, types::int);
        $resolvedStatic = types::object(\stdClass::class, types::int);

        yield [$static, $resolvedStatic];
        yield [types::classString($static), types::classString($resolvedStatic)];
        yield [types::nonEmptyList($static), types::nonEmptyList($resolvedStatic)];
        yield [types::list($static), types::list($resolvedStatic)];
        yield [types::shape([$static]), types::shape([$resolvedStatic])];
        yield [types::unsealedShape(['a' => types::optional($static)]), types::unsealedShape(['a' => types::optional($resolvedStatic)])];
        yield [types::nonEmptyArray(valueType: $static), types::nonEmptyArray(valueType: $resolvedStatic)];
        yield [types::array(valueType: $static), types::array(valueType: $resolvedStatic)];
        yield [types::iterable(valueType: $static), types::iterable(valueType: $resolvedStatic)];
        yield [types::object(\stdClass::class, $static), types::object(\stdClass::class, $resolvedStatic)];
        yield [types::closure([$static], $static), types::closure([$resolvedStatic], $resolvedStatic)];
        yield [types::callable([types::variadicParam($static)]), types::callable([types::variadicParam($resolvedStatic)])];
        yield [types::keyOf($static), types::keyOf($resolvedStatic)];
        yield [types::valueOf($static), types::valueOf($resolvedStatic)];
        yield [types::nullable($static), types::nullable($resolvedStatic)];
        yield [types::intersection($static, types::int), types::intersection($resolvedStatic, types::int)];
        yield [types::union($static, types::int), types::union($resolvedStatic, types::int)];
    }

    #[DataProvider('types')]
    public function testItKeepsSimpleTypesSame(Type $type, Type $expectedType): void
    {
        $resolver = new StaticResolver(\stdClass::class);

        $resolvedType = $type->accept($resolver);

        self::assertEquals($expectedType, $resolvedType);
    }
}
