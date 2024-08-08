<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeReflector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Typhoon\ChangeDetector\ConstantChangeDetector;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Type\types;
use function Typhoon\Reflection\Internal\get_namespace;

#[CoversClass(DefinedConstantReflector::class)]
final class DefinedConstantReflectorTest extends TestCase
{
    public function testItReturnsNullForUndefinedConstant(): void
    {
        $reflector = new DefinedConstantReflector();

        $data = $reflector->reflectConstant(Id::constant(self::class));

        self::assertNull($data);
    }

    /**
     * @return \Generator<non-empty-string, array{non-empty-string, ?non-empty-string, mixed}>
     */
    public static function definedConstantsWithoutNAN(): \Generator
    {
        foreach (get_defined_constants(categorize: true) as $category => $constants) {
            foreach ($constants as $name => $value) {
                if ($name === 'NAN') {
                    continue;
                }

                $extension = $category === 'user' ? null : (new \ReflectionExtension($category))->name;

                \assert($name !== '');
                \assert($extension !== '');

                yield $name => [Id::constant($name), $extension, $value];

                return;
            }
        }
    }

    #[DataProvider('definedConstantsWithoutNAN')]
    public function testDefinedConstantsWithoutNAN(ConstantId $id, ?string $extension, mixed $value): void
    {
        $reflector = new DefinedConstantReflector();

        $data = $reflector->reflectConstant($id);

        self::assertNotNull($data);
        self::assertSame($value, $data[Data::ValueExpression]->evaluate());
        self::assertEquals(new TypeData(inferred: types::value($value)), $data[Data::Type]);
        self::assertSame($extension, $data[Data::PhpExtension]);
        self::assertSame($extension !== null, $data[Data::InternallyDefined]);
        self::assertNull($data[Data::PhpDoc]);
        self::assertNull($data[Data::Location]);
        self::assertNull($data[Data::Deprecation]);
        self::assertSame(get_namespace($id->name), $data[Data::Namespace]);
        self::assertEquals(ConstantChangeDetector::fromName($id->name), $data[Data::ChangeDetector]);
    }

    public function testNAN(): void
    {
        $id = Id::constant('NAN');
        $reflector = new DefinedConstantReflector();

        $data = $reflector->reflectConstant($id);

        self::assertNotNull($data);
        self::assertNan($data[Data::ValueExpression]->evaluate());
        self::assertSame(serialize(new TypeData(inferred: types::float(NAN))), serialize($data[Data::Type]));
        self::assertSame('standard', $data[Data::PhpExtension]);
        self::assertTrue($data[Data::InternallyDefined]);
        self::assertNull($data[Data::PhpDoc]);
        self::assertNull($data[Data::Location]);
        self::assertNull($data[Data::Deprecation]);
        self::assertSame('', $data[Data::Namespace]);
        self::assertInstanceOf(ConstantChangeDetector::class, $data[Data::ChangeDetector]);
    }
}
