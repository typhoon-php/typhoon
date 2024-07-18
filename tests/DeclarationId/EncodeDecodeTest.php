<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Id::class)]
#[CoversClass(ConstId::class)]
#[CoversClass(NamedFunctionId::class)]
#[CoversClass(AnonymousFunctionId::class)]
#[CoversClass(NamedClassId::class)]
#[CoversClass(AnonymousClassId::class)]
#[CoversClass(AliasId::class)]
#[CoversClass(TemplateId::class)]
#[CoversClass(ClassConstId::class)]
#[CoversClass(PropertyId::class)]
#[CoversClass(MethodId::class)]
#[CoversClass(ParameterId::class)]
final class EncodeDecodeTest extends TestCase
{
    /**
     * @return \Generator<int, array{Id, non-empty-string}>
     */
    public static function ids(): \Generator
    {
        yield [Id::const('CONST'), '["c","CONST"]'];
        yield [Id::namedFunction('fn'), '["nf","fn"]'];
        yield [Id::anonymousFunction('file', 10, 20), '["af","file",10,20]'];
        yield [Id::anonymousFunction('file', 10), '["af","file",10]'];
        yield [Id::namedClass('class'), '["nc","class"]'];
        yield [Id::anonymousClass('file', 12), '["ac","file",12]'];
        yield [Id::anonymousClass('file', 12, 33), '["ac","file",12,33]'];
        yield [Id::parameter(Id::namedFunction('test'), 'a'), '["pa",["nf","test"],"a"]'];
        yield [Id::parameter(Id::anonymousFunction('file', 10, 23), 'a'), '["pa",["af","file",10,23],"a"]'];
        yield [Id::parameter(Id::method(\stdClass::class, 'test'), 'a'), '["pa",["m",["nc","stdClass"],"test"],"a"]'];
        yield [Id::property('a-class', 'prop'), '["p",["nc","a-class"],"prop"]'];
        yield [Id::method('a-class', 'method'), '["m",["nc","a-class"],"method"]'];
        yield [Id::classConst('a-class', 'const'), '["cc",["nc","a-class"],"const"]'];
        yield [Id::alias('a-class', 'alias'), '["a",["nc","a-class"],"alias"]'];
        yield [Id::alias(Id::anonymousClass('file', 12), 'alias'), '["a",["ac","file",12],"alias"]'];
        yield [Id::template(Id::anonymousClass('file', 12), 'alias'), '["t",["ac","file",12],"alias"]'];
    }

    #[DataProvider('ids')]
    public function testItEncodesToExpectedValue(Id $id, string $expected): void
    {
        $encoded = $id->encode();

        self::assertSame($expected, $encoded);
    }

    #[DataProvider('ids')]
    public function testItDecodesToSameValue(Id $id): void
    {
        $encoded = $id->encode();

        $decoded = Id::decode($encoded);

        self::assertTrue($id->equals($decoded));
    }
}
