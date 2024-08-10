<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\DeclarationId\Id;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

#[CoversClass(Internal\AliasType::class)]
#[CoversClass(Internal\ArgumentType::class)]
#[CoversClass(Internal\ArrayType::class)]
#[CoversClass(Internal\CallableType::class)]
#[CoversClass(Internal\ClassConstantMaskType::class)]
#[CoversClass(Internal\ClassConstantType::class)]
#[CoversClass(Internal\ClassStringType::class)]
#[CoversClass(Internal\ConditionalType::class)]
#[CoversClass(Internal\ConstantType::class)]
#[CoversClass(Internal\FloatType::class)]
#[CoversClass(Internal\FloatValueType::class)]
#[CoversClass(Internal\IntersectionType::class)]
#[CoversClass(Internal\IntMaskType::class)]
#[CoversClass(Internal\IntType::class)]
#[CoversClass(Internal\IntValueType::class)]
#[CoversClass(Internal\IterableType::class)]
#[CoversClass(Internal\KeyType::class)]
#[CoversClass(Internal\ListType::class)]
#[CoversClass(Internal\LiteralType::class)]
#[CoversClass(Internal\NamedObjectType::class)]
#[CoversClass(Internal\NonEmptyArrayType::class)]
#[CoversClass(Internal\NotType::class)]
#[CoversClass(Internal\ObjectType::class)]
#[CoversClass(Internal\OffsetType::class)]
#[CoversClass(Internal\ParentType::class)]
#[CoversClass(Internal\SelfType::class)]
#[CoversClass(Internal\StaticType::class)]
#[CoversClass(Internal\StringValueType::class)]
#[CoversClass(Internal\TemplateType::class)]
#[CoversClass(Internal\UnionType::class)]
#[CoversClass(Internal\VarianceAwareType::class)]
#[CoversClass(types::class)]
final class TypeRoutingTest extends TestCase
{
    /**
     * @return \Generator<Type, array{0: non-empty-string, 1?: list<mixed>}>
     */
    public static function cases(): \Generator
    {
        yield types::never => ['never'];
        yield types::void => ['void'];
        yield types::null => ['null'];
        yield types::true => ['true'];
        yield types::false => ['false'];
        yield types::int => ['int', [types::PHP_INT_MIN, types::PHP_INT_MAX]];
        yield types::PHP_INT_MIN => ['constant', [Id::constant('PHP_INT_MIN')]];
        yield types::PHP_INT_MAX => ['constant', [Id::constant('PHP_INT_MAX')]];
        yield types::negativeInt => ['int', [types::PHP_INT_MIN, types::int(-1)]];
        yield types::nonPositiveInt => ['int', [types::PHP_INT_MIN, types::int(0)]];
        yield types::nonNegativeInt => ['int', [types::int(0), types::PHP_INT_MAX]];
        yield types::positiveInt => ['int', [types::int(1), types::PHP_INT_MAX]];
        yield types::nonZeroInt => ['union', [[types::positiveInt, types::negativeInt]]];
        yield types::literalInt => ['literal', [types::int]];
        yield types::float => ['float', [types::PHP_FLOAT_MIN, types::PHP_FLOAT_MAX]];
        yield types::PHP_FLOAT_MIN => ['constant', [Id::constant('PHP_FLOAT_MIN')]];
        yield types::PHP_FLOAT_MAX => ['constant', [Id::constant('PHP_FLOAT_MAX')]];
        yield types::literalFloat => ['literal', [types::float]];
        yield types::string => ['string'];
        yield types::nonEmptyString => ['intersection', [[types::string, types::not(types::string(''))]]];
        yield types::numericString => ['intersection', [[types::string, types::numeric]]];
        yield types::truthyString => ['intersection', [[types::string, types::not(types::string('')), types::not(types::string('0'))]]];
        yield types::nonFalsyString => ['intersection', [[types::string, types::not(types::string('')), types::not(types::string('0'))]]];
    }

    /**
     * @return \Generator<non-empty-string, array{Type, non-empty-string, list<mixed>}>
     */
    public static function namedCases(): \Generator
    {
        $index = 0;

        foreach (self::cases() as $type => $case) {
            yield $index . '. ' . stringify($type) => [$type, $case[0], $case[1] ?? []];
            ++$index;
        }
    }

    #[DataProvider('namedCases')]
    public function test(Type $type, string $expectedMethod, array $expectedArgs = []): void
    {
        $type->accept(
            new
            /** @extends DefaultTypeVisitor<null> */
            class ($type, $expectedMethod, $expectedArgs) extends DefaultTypeVisitor {
                public function __construct(
                    private readonly Type $expectedType,
                    private readonly string $expectedMethod,
                    private readonly array $expectedArgs,
                ) {}

                protected function default(Type $type): mixed
                {
                    $trace = debug_backtrace(limit: 2)[1];

                    Assert::assertSame($trace['function'], $this->expectedMethod);
                    Assert::assertEquals($trace['args'] ?? [], [$this->expectedType, ...$this->expectedArgs]);

                    return null;
                }
            },
        );
    }
}
