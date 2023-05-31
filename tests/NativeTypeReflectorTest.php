<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\ClassLocator\ClassLocatorChain;
use ExtendedTypeSystem\Reflection\ClassLocator\SingleClassLocator;
use ExtendedTypeSystem\Reflection\Stub\Base;
use ExtendedTypeSystem\Reflection\Stub\Iface;
use ExtendedTypeSystem\Reflection\Stub\Main;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

/**
 * @internal
 * @covers \ExtendedTypeSystem\Reflection\TypeReflector
 */
final class NativeTypeReflectorTest extends TestCase
{
    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsNativeTypesAtProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                public {$type} \$test;
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->propertyType('test')->resolved;

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsNativeTypesAtPromotedProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                public function __construct(public {$type} \$test) {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->propertyType('test')->resolved;

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtProperty(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Reflection\Stub;
            class Base extends \ArrayObject {
                public parent $test;
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->propertyType('test')->resolved;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtPromotedProperty(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Reflection\Stub;
            class Base extends \ArrayObject {
                public function __construct(public parent $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->propertyType('test')->resolved;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     * @dataProvider callableType
     */
    public function testItReflectsNativeTypesAtMethodParameter(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                public function test({$type} \$test) {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->method('test')->parameterType('test')->resolved;

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtMethodParameter(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Reflection\Stub;
            class Base extends \ArrayObject {
                public function test(parent $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->method('test')->parameterType('test')->resolved;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     * @dataProvider staticType
     * @dataProvider callableType
     * @dataProvider voidType
     * @dataProvider neverType
     */
    public function testItReflectsNativeTypesAtMethodReturn(string $type, Type $expectedType): void
    {
        $typeDeclaration = $type === '' ? '' : ': ' . $type;
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                public function test(){$typeDeclaration} {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->method('test')->returnType()->resolved;

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtMethodReturn(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Reflection\Stub;
            class Base extends \ArrayObject {
                public function test(): parent {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->method('test')->returnType()->resolved;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsStaticNativeTypeAsNamedObjectAtMethodReturnOfFinalClass(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Reflection\Stub;
            final class Main {
                public function test(): static {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->method('test')->returnType()->resolved;

        assertEquals(types::object(Main::class), $reflectedType);
    }

    public function testItReflectsInheritedStaticNativeTypeAtMethodReturn(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Reflection\Stub;
            class Base {
                public function test(): static {}
            }
            class Main extends Base {}
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->method('test')->returnType()->resolved;

        assertEquals(types::static(Base::class), $reflectedType);
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function nativeTypes(): \Generator
    {
        yield from TypeProvider::nativeTypes();
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function callableType(): \Generator
    {
        yield from TypeProvider::callableType();
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function voidType(): \Generator
    {
        yield from TypeProvider::voidType();
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function neverType(): \Generator
    {
        yield from TypeProvider::neverType();
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function staticType(): \Generator
    {
        yield from TypeProvider::staticType();
    }

    private function locateCode(string $code): ClassLocator
    {
        return new ClassLocatorChain([
            ...array_map(
                static fn (string $class): SingleClassLocator => new SingleClassLocator($class, new Source('test', '<?php ' . $code)),
                [Base::class, Main::class, Iface::class],
            ),
            new SingleClassLocator(\ArrayObject::class, new Source('ArrayObject', '<?php class ArrayObject {}')),
        ]);
    }
}
