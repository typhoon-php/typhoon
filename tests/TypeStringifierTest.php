<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\TypeStringifier;

use PHP\ExtendedTypeSystem\Type\ArrayKeyT;
use PHP\ExtendedTypeSystem\Type\ArrayShapeItem;
use PHP\ExtendedTypeSystem\Type\ArrayShapeT;
use PHP\ExtendedTypeSystem\Type\ArrayT;
use PHP\ExtendedTypeSystem\Type\AtClass;
use PHP\ExtendedTypeSystem\Type\AtFunction;
use PHP\ExtendedTypeSystem\Type\AtMethod;
use PHP\ExtendedTypeSystem\Type\BoolT;
use PHP\ExtendedTypeSystem\Type\CallableParameter;
use PHP\ExtendedTypeSystem\Type\CallableT;
use PHP\ExtendedTypeSystem\Type\ClosureT;
use PHP\ExtendedTypeSystem\Type\FalseT;
use PHP\ExtendedTypeSystem\Type\FloatLiteralT;
use PHP\ExtendedTypeSystem\Type\FloatT;
use PHP\ExtendedTypeSystem\Type\GeneratorT;
use PHP\ExtendedTypeSystem\Type\IntersectionT;
use PHP\ExtendedTypeSystem\Type\IntLiteralT;
use PHP\ExtendedTypeSystem\Type\IntRangeT;
use PHP\ExtendedTypeSystem\Type\IntT;
use PHP\ExtendedTypeSystem\Type\IterableT;
use PHP\ExtendedTypeSystem\Type\ListT;
use PHP\ExtendedTypeSystem\Type\MixedT;
use PHP\ExtendedTypeSystem\Type\NamedObjectT;
use PHP\ExtendedTypeSystem\Type\NeverT;
use PHP\ExtendedTypeSystem\Type\NonEmptyArrayT;
use PHP\ExtendedTypeSystem\Type\NonEmptyListT;
use PHP\ExtendedTypeSystem\Type\NonEmptyStringT;
use PHP\ExtendedTypeSystem\Type\NullableT;
use PHP\ExtendedTypeSystem\Type\NullT;
use PHP\ExtendedTypeSystem\Type\NumericStringT;
use PHP\ExtendedTypeSystem\Type\NumericT;
use PHP\ExtendedTypeSystem\Type\ObjectT;
use PHP\ExtendedTypeSystem\Type\PositiveIntT;
use PHP\ExtendedTypeSystem\Type\ResourceT;
use PHP\ExtendedTypeSystem\Type\ScalarT;
use PHP\ExtendedTypeSystem\Type\StringLiteralT;
use PHP\ExtendedTypeSystem\Type\StringT;
use PHP\ExtendedTypeSystem\Type\TemplateT;
use PHP\ExtendedTypeSystem\Type\TrueT;
use PHP\ExtendedTypeSystem\Type\Type;
use PHP\ExtendedTypeSystem\Type\TypeAlias;
use PHP\ExtendedTypeSystem\Type\UnionT;
use PHP\ExtendedTypeSystem\Type\VoidT;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \PHP\ExtendedTypeSystem\TypeStringifier\TypeStringifier
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
