<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use PHPyh\PsalmTester\PsalmTest as Test;
use PHPyh\PsalmTester\PsalmTester;

final class PsalmTest extends TestCase
{
    private ?PsalmTester $psalmTester = null;

    /**
     * @template TType
     * @param Type<TType> $_type
     * @return TType
     */
    public static function extractType(Type $_type): mixed
    {
        /** @var TType */
        return null;
    }

    #[TestWith([__DIR__ . '/psalm/AnyLiteralIntType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/AnyLiteralStringType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ArrayShapeType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ArrayType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/BoolType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/CallableType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ClassConstantType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ClassStringLiteralType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ClassStringType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ClosureType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ConditionalType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ConstantType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/FloatType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/IntersectionType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/IntMaskOfType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/IntMaskType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/IntRangeType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/IntType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/IterableType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/KeyOfType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ListType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/LiteralType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/MixedType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/NamedClassStringType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/NamedObjectType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/NeverType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/NonEmptyType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/NullType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/NumericStringType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ObjectShapeType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ObjectType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/OffsetType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ResourceType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/StaticType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/StringType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/TemplateType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/TruthyStringType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/types.phpt'])]
    #[TestWith([__DIR__ . '/psalm/UnionType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/ValueOfType.phpt'])]
    #[TestWith([__DIR__ . '/psalm/VoidType.phpt'])]
    public function testPhptFiles(string $phptFile): void
    {
        $this->psalmTester ??= PsalmTester::create();
        $this->psalmTester->test(Test::fromPhptFile($phptFile));
    }
}
