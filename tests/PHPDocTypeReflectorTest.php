<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\ClassLocator\ClassLocatorChain;
use ExtendedTypeSystem\ClassLocator\SingleClassLocator;
use ExtendedTypeSystem\Stub\Base;
use ExtendedTypeSystem\Stub\Iface;
use ExtendedTypeSystem\Stub\Main;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

/**
 * @internal
 * @covers \ExtendedTypeSystem\TypeReflector
 * @covers \ExtendedTypeSystem\TypeReflector\ClassLikeScope
 * @covers \ExtendedTypeSystem\TypeReflector\ClassReflectionFactory
 * @covers \ExtendedTypeSystem\TypeReflector\FindClassVisitor
 * @covers \ExtendedTypeSystem\TypeReflector\MethodScope
 * @covers \ExtendedTypeSystem\TypeReflector\PHPDoc
 * @covers \ExtendedTypeSystem\TypeReflector\PHPDocParser
 * @covers \ExtendedTypeSystem\TypeReflector\PropertyScope
 * @covers \ExtendedTypeSystem\TypeReflector\TypeResolver
 */
final class PHPDocTypeReflectorTest extends TestCase
{
    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                /** @var {$type} */
                public \$test;
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->propertyType('test');

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtPromotedProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                /** @param {$type} \$test */
                public function __construct(public \$test) {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->propertyType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtProperty(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @var parent */
                public $test;
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtPromotedProperty(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @param parent $test */
                public function __construct(public $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtMethodParameter(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                /** @param {$type} \$test */
                public function test(mixed \$test) {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->parameterType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtMethodParameter(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @param parent $test */
                public function test(mixed $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->parameterType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtMethodReturn(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                /** @return {$type} */
                public function test() {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->returnType;

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsInheritedParentNativeTypeAtMethodReturn(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @return parent */
                public function test() {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->returnType;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsStaticNativeTypeAsNamedObjectAtMethodReturnOfFinalClass(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            final class Main {
                /** @return static */
                public function test() {}
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->returnType;

        assertEquals(types::object(Main::class), $reflectedType);
    }

    public function testItReflectsInheritedStaticNativeTypeAtMethodReturn(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base {
                /** @return static */
                public function test() {}
            }
            class Main extends Base {}
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->returnType;

        assertEquals(types::static(Base::class), $reflectedType);
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function types(): \Generator
    {
        yield from TypeProvider::all();
    }

    private function locateCode(string $code): ClassLocator
    {
        return new ClassLocatorChain(array_map(
            static fn (string $class): SingleClassLocator => new SingleClassLocator($class, new Source('test', '<?php '.$code)),
            [Main::class, Base::class, Iface::class],
        ));
    }
}
