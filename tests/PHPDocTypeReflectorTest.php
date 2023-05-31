<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\ClassLocator\ClassLocatorChain;
use ExtendedTypeSystem\Reflection\ClassLocator\DeterministicClassLocator;
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
final class PHPDocTypeReflectorTest extends TestCase
{
    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                /** @var {$type} */
                public \$test;
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->propertyType('test')->resolved;

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtPromotedProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                /** @param {$type} \$test */
                public function __construct(public \$test) {}
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
                /** @var parent */
                public $test;
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
                /** @param parent $test */
                public function __construct(public $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->propertyType('test')->resolved;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtMethodParameter(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                /** @param {$type} \$test */
                public function test(mixed \$test) {}
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
                /** @param parent $test */
                public function test(mixed $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClassLike(Main::class)->method('test')->parameterType('test')->resolved;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativeTypesAtMethodReturn(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Reflection\\Stub;
            class Main extends \\ArrayObject {
                /** @return {$type} */
                public function test() {}
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
                /** @return parent */
                public function test() {}
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
                /** @return static */
                public function test() {}
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
                /** @return static */
                public function test() {}
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
    public function types(): \Generator
    {
        yield from TypeProvider::all();
    }

    private function locateCode(string $code): ClassLocator
    {
        return new ClassLocatorChain([
            new DeterministicClassLocator(new Source('<?php ' . $code), Base::class, Main::class, Iface::class),
            new DeterministicClassLocator(new Source('<?php class ArrayObject {}'), \ArrayObject::class),
        ]);
    }
}
