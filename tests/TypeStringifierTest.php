<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Type\ArrayKeyT;
use ExtendedTypeSystem\Type\ArrayShapeItem;
use ExtendedTypeSystem\Type\ArrayShapeT;
use ExtendedTypeSystem\Type\ArrayT;
use ExtendedTypeSystem\Type\AtClass;
use ExtendedTypeSystem\Type\AtFunction;
use ExtendedTypeSystem\Type\AtMethod;
use ExtendedTypeSystem\Type\BoolT;
use ExtendedTypeSystem\Type\CallableArrayT;
use ExtendedTypeSystem\Type\CallableParameter;
use ExtendedTypeSystem\Type\CallableStringT;
use ExtendedTypeSystem\Type\CallableT;
use ExtendedTypeSystem\Type\ClassConstantT;
use ExtendedTypeSystem\Type\ClassStringT;
use ExtendedTypeSystem\Type\ClosedResourceT;
use ExtendedTypeSystem\Type\ClosureT;
use ExtendedTypeSystem\Type\ConstantT;
use ExtendedTypeSystem\Type\EnumStringT;
use ExtendedTypeSystem\Type\FalseT;
use ExtendedTypeSystem\Type\FloatLiteralT;
use ExtendedTypeSystem\Type\FloatT;
use ExtendedTypeSystem\Type\GeneratorT;
use ExtendedTypeSystem\Type\InterfaceStringT;
use ExtendedTypeSystem\Type\IntersectionT;
use ExtendedTypeSystem\Type\IntLiteralT;
use ExtendedTypeSystem\Type\IntRangeT;
use ExtendedTypeSystem\Type\IntT;
use ExtendedTypeSystem\Type\IterableT;
use ExtendedTypeSystem\Type\KeyOfT;
use ExtendedTypeSystem\Type\ListT;
use ExtendedTypeSystem\Type\LiteralIntT;
use ExtendedTypeSystem\Type\LiteralStringT;
use ExtendedTypeSystem\Type\MixedT;
use ExtendedTypeSystem\Type\NamedClassStringT;
use ExtendedTypeSystem\Type\NamedObjectT;
use ExtendedTypeSystem\Type\NeverT;
use ExtendedTypeSystem\Type\NonEmptyArrayT;
use ExtendedTypeSystem\Type\NonEmptyListT;
use ExtendedTypeSystem\Type\NonEmptyStringT;
use ExtendedTypeSystem\Type\NullableT;
use ExtendedTypeSystem\Type\NullT;
use ExtendedTypeSystem\Type\NumericStringT;
use ExtendedTypeSystem\Type\NumericT;
use ExtendedTypeSystem\Type\ObjectT;
use ExtendedTypeSystem\Type\PositiveIntT;
use ExtendedTypeSystem\Type\ResourceT;
use ExtendedTypeSystem\Type\ScalarT;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\StringLiteralT;
use ExtendedTypeSystem\Type\StringT;
use ExtendedTypeSystem\Type\TemplateT;
use ExtendedTypeSystem\Type\TraitStringT;
use ExtendedTypeSystem\Type\TrueT;
use ExtendedTypeSystem\Type\UnionT;
use ExtendedTypeSystem\Type\ValueOfT;
use ExtendedTypeSystem\Type\VoidT;
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
        yield [new NeverT(), 'never'];
        yield [new VoidT(), 'void'];
        yield [new MixedT(), 'mixed'];
        yield [new NullT(), 'null'];
        yield [new NullableT(new StringT()), '?string'];
        yield [new TrueT(), 'true'];
        yield [new FalseT(), 'false'];
        yield [new BoolT(), 'bool'];
        yield [new PositiveIntT(), 'positive-int'];
        yield [new IntT(), 'int'];
        yield [new IntLiteralT(123), '123'];
        yield [new IntLiteralT(-123), '-123'];
        yield [new IntRangeT(min: 23), 'int<23, max>'];
        yield [new IntRangeT(max: 23), 'int<min, 23>'];
        yield [new IntRangeT(min: -100, max: 234), 'int<-100, 234>'];
        yield [new FloatT(), 'float'];
        yield [new FloatLiteralT(0.234), '0.234'];
        yield [new FloatLiteralT(-0.234), '-0.234'];
        yield [new NumericT(), 'numeric'];
        yield [new ArrayKeyT(), 'array-key'];
        yield [new NumericStringT(), 'numeric-string'];
        yield [new NonEmptyStringT(), 'non-empty-string'];
        yield [new StringT(), 'string'];
        yield [new StringLiteralT('abcd'), "'abcd'"];
        yield [new StringLiteralT("a'bcd"), "'a\\'bcd'"];
        yield [new StringLiteralT("a\\\\'bcd"), "'a\\\\\\\\\\'bcd'"];
        yield [new ScalarT(), 'scalar'];
        yield [new ResourceT(), 'resource'];
        yield [new NonEmptyListT(), 'non-empty-list'];
        yield [new NonEmptyListT(new StringT()), 'non-empty-list<string>'];
        yield [new ListT(), 'list'];
        yield [new ListT(new StringT()), 'list<string>'];
        yield [new NonEmptyArrayT(), 'non-empty-array'];
        yield [new NonEmptyArrayT(valueType: new StringT()), 'non-empty-array<string>'];
        yield [new NonEmptyArrayT(new StringT(), new IntT()), 'non-empty-array<string, int>'];
        yield [new ArrayT(), 'array'];
        yield [new ArrayT(valueType: new StringT()), 'array<string>'];
        yield [new ArrayT(new StringT(), new IntT()), 'array<string, int>'];
        yield [new ArrayShapeT([]), 'list{}'];
        yield [new ArrayShapeT(sealed: false), 'array'];
        yield [new ArrayShapeT([new IntT()]), 'list{int}'];
        yield [new ArrayShapeT([new IntT(), 'a' => new StringT()]), 'array{0: int, a: string}'];
        yield [new ArrayShapeT([new IntT(), 'a' => new StringT()], sealed: false), 'array{0: int, a: string, ...}'];
        yield [new ArrayShapeT([new ArrayShapeItem(new IntT(), optional: true)]), 'list{0?: int}'];
        yield [new ArrayShapeT([new ArrayShapeItem(new IntT(), optional: true)], sealed: false), 'array{0?: int, ...}'];
        yield [new ArrayShapeT(['a' => new ArrayShapeItem(new IntT(), optional: true)]), 'array{a?: int}'];
        yield [new ObjectT(), 'object'];
        yield [new NamedObjectT(\ArrayObject::class), 'ArrayObject'];
        yield [new NamedObjectT(\ArrayObject::class, new ArrayKeyT(), new StringT()), 'ArrayObject<array-key, string>'];
        yield [new UnionT(new IntT(), new StringT()), 'int|string'];
        yield [new UnionT(new IntT(), new UnionT(new StringT(), new FloatT())), 'int|string|float'];
        yield [new UnionT(new IntT(), new IntersectionT(new StringT(), new FloatT())), 'int|(string&float)'];
        yield [new IntersectionT(new IntT(), new StringT()), 'int&string'];
        yield [new IntersectionT(new IntT(), new IntersectionT(new StringT(), new FloatT())), 'int&string&float'];
        yield [new IntersectionT(new IntT(), new UnionT(new StringT(), new FloatT())), 'int&(string|float)'];
        yield [new IterableT(), 'iterable'];
        yield [new IterableT(valueType: new StringT()), 'iterable<string>'];
        yield [new IterableT(new StringT(), new IntT()), 'iterable<string, int>'];
        yield [new CallableT(), 'callable'];
        yield [new CallableT(returnType: new VoidT()), 'callable(): void'];
        yield [new CallableT([new StringT()]), 'callable(string)'];
        yield [new CallableT([new CallableParameter(new StringT(), hasDefault: true)]), 'callable(string=)'];
        yield [new CallableT([new CallableParameter(new StringT(), variadic: true)]), 'callable(string...)'];
        yield [new CallableT([new CallableParameter(new StringT(), variadic: true)], new NeverT()), 'callable(string...): never'];
        yield [new ClosureT(), 'Closure'];
        yield [new ClosureT(returnType: new VoidT()), 'Closure(): void'];
        yield [new ClosureT([new StringT()]), 'Closure(string)'];
        yield [new ClosureT([new CallableParameter(new StringT(), hasDefault: true)]), 'Closure(string=)'];
        yield [new ClosureT([new CallableParameter(new StringT(), variadic: true)]), 'Closure(string...)'];
        yield [new ClosureT([new CallableParameter(new StringT(), variadic: true)], new NeverT()), 'Closure(string...): never'];
        yield [new TemplateT('T', new AtClass(\ArrayObject::class)), 'T:ArrayObject'];
        yield [new TemplateT('T', new AtFunction('strval')), 'T:strval()'];
        yield [new TemplateT('T', new AtMethod(\ArrayObject::class, 'method')), 'T:ArrayObject::method()'];
        yield [new GeneratorT(new StringT(), new IntT(), new FloatT(), new VoidT()), 'Generator<string, int, float, void>'];
        yield [new LiteralStringT(), 'literal-string'];
        yield [new LiteralIntT(), 'literal-int'];
        yield [new IntRangeT(), 'int'];
        yield [new NamedClassStringT(new ObjectT()), 'class-string<object>'];
        yield [new ClassStringT(), 'class-string'];
        yield [new CallableStringT(), 'callable-string'];
        yield [new InterfaceStringT(), 'interface-string'];
        yield [new EnumStringT(), 'enum-string'];
        yield [new TraitStringT(), 'trait-string'];
        yield [new CallableArrayT(), 'callable-array'];
        yield [new StaticT(\stdClass::class), 'static'];
        yield [new StaticT(\stdClass::class, new StringT(), new IntT()), 'static<string, int>'];
        yield [new ClosedResourceT(), 'closed-resource'];
        yield [new ConstantT('test'), 'test'];
        yield [new ClassConstantT(\stdClass::class, 'test'), 'stdClass::test'];
        yield [new KeyOfT(new ListT()), 'key-of<list>'];
        yield [new ValueOfT(new ListT()), 'value-of<list>'];
        yield [
            new /**
             * @psalm-immutable
             * @extends TypeAlias<string>
             */ class () extends TypeAlias {
                public function type(): Type
                {
                    return new StringT();
                }
            },
            'string',
        ];
    }
}
