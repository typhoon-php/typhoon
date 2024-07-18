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
final class DescribeTest extends TestCase
{
    /**
     * @return \Generator<int, array{Id, non-empty-string}>
     */
    public static function ids(): \Generator
    {
        yield [Id::const('PHP_INT_MAX'), 'constant PHP_INT_MAX'];
        yield [Id::namedFunction('trim'), 'function trim()'];
        yield [Id::anonymousFunction('/path/to/file', 10, 20), 'anonymous function at /path/to/file:10:20'];
        yield [Id::anonymousFunction('/path/to/file', 10), 'anonymous function at /path/to/file:10'];
        yield [Id::namedClass(\ArrayObject::class), 'class ArrayObject'];
        yield [Id::anonymousClass('/path/to/file', 12), 'anonymous class at /path/to/file:12'];
        yield [Id::anonymousClass('/path/to/file', 12, 33), 'anonymous class at /path/to/file:12:33'];
        yield [Id::parameter(Id::namedFunction('trim'), 'a'), 'parameter $a of function trim()'];
        yield [Id::parameter(Id::anonymousFunction('/path/to/file', 10, 23), 'a'), 'parameter $a of anonymous function at /path/to/file:10:23'];
        yield [Id::parameter(Id::method(\ArrayObject::class, 'offsetExists'), 'a'), 'parameter $a of method ArrayObject::offsetExists()'];
        yield [Id::property(\ArrayObject::class, 'prop'), 'property ArrayObject::$prop'];
        yield [Id::property(Id::anonymousClass('/path/to/file', 12), 'prop'), 'property $prop of anonymous class at /path/to/file:12'];
        yield [Id::method(\ArrayObject::class, 'offsetExists'), 'method ArrayObject::offsetExists()'];
        yield [Id::method(Id::anonymousClass('/path/to/file', 12), 'offsetExists'), 'method offsetExists of anonymous class at /path/to/file:12'];
        yield [Id::classConst(\ArrayObject::class, 'ARRAY_AS_PROPS'), 'constant ArrayObject::ARRAY_AS_PROPS'];
        yield [Id::classConst(Id::anonymousClass('/path/to/file', 12), 'ARRAY_AS_PROPS'), 'constant ARRAY_AS_PROPS of anonymous class at /path/to/file:12'];
        yield [Id::alias(\ArrayObject::class, 'Key'), 'type alias Key of class ArrayObject'];
        yield [Id::alias(Id::anonymousClass('/path/to/file', 12), 'Key'), 'type alias Key of anonymous class at /path/to/file:12'];
        yield [Id::template(Id::anonymousClass('/path/to/file', 12), 'T'), 'template T of anonymous class at /path/to/file:12'];
    }

    #[DataProvider('ids')]
    public function test(Id $id, string $expected): void
    {
        $encoded = $id->describe();

        self::assertSame($expected, $encoded);
    }
}
