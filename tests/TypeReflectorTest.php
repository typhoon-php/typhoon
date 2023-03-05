<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Source\ClassLocatorChain;
use ExtendedTypeSystem\Source\SingleClassLocator;
use ExtendedTypeSystem\Source\Source;
use ExtendedTypeSystem\Stub\Base;
use ExtendedTypeSystem\Stub\Main;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

/**
 * @internal
 * @covers \ExtendedTypeSystem\TypeReflector
 * @covers \ExtendedTypeSystem\TypeReflector\ClassLikeContext
 * @covers \ExtendedTypeSystem\TypeReflector\ClassVisitor
 * @covers \ExtendedTypeSystem\TypeReflector\Context
 * @covers \ExtendedTypeSystem\TypeReflector\MethodContext
 * @covers \ExtendedTypeSystem\TypeReflector\PHPDocParser
 * @covers \ExtendedTypeSystem\TypeReflector\PropertyContext
 * @covers \ExtendedTypeSystem\TypeReflector\TypeResolver
 */
final class TypeReflectorTest extends TestCase
{
    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsNativeTypesAtProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                public {$type} \$test;
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->propertyType('test');

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsNativeTypesAtPromotedProperty(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                public function __construct(public {$type} \$test) {}
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
                public parent $test;
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
                public function __construct(public parent $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     * @dataProvider callableType
     */
    public function testItReflectsNativeTypesAtMethodParameter(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                public function test({$type} \$test) {}
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
                public function test(parent $test) {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->parameterType('test');

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
        $typeDeclaration = $type === '' ? '' : ': '.$type;
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Main extends \\ArrayObject {
                public function test(){$typeDeclaration} {}
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
                public function test(): parent {}
            }
            class Main extends Base {
            }
            PHP;
        $typeReflector = new TypeReflector($this->locateCode($code));

        $reflectedType = $typeReflector->reflectClass(Main::class)->method('test')->returnType;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsInheritedStaticNativeTypeAtMethodReturn(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base {
                public function test(): static {}
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
    public function nativeTypes(): \Generator
    {
        yield 'no type' => ['', types::mixed];
        yield 'bool' => ['bool', types::bool];
        yield 'int' => ['int', types::int];
        yield 'float' => ['float', types::float];
        yield 'string' => ['string', types::string];
        yield 'array' => ['array', types::array()];
        yield 'iterable' => ['iterable', types::iterable()];
        yield 'object' => ['object', types::object];
        yield 'mixed' => ['mixed', types::mixed];
        yield 'Closure' => ['\Closure', types::object(\Closure::class)];
        yield 'string|int|null' => ['string|int|null', types::union(types::string, types::int, types::null)];
        yield 'string|false' => ['string|false', types::union(types::string, types::false)];
        yield 'Countable&Traversable' => ['\Countable&\Traversable', types::intersection(types::object(\Countable::class), types::object(\Traversable::class))];
        yield '?int' => ['?int', types::nullable(types::int)];
        yield 'self' => ['self', types::object(Main::class)];
        yield 'ArrayObject parent' => ['parent', types::object(\ArrayObject::class)];

        if (\PHP_VERSION_ID >= 80200) {
            yield 'null' => ['null', types::null];
            yield 'true' => ['true', types::true];
            yield 'false' => ['false', types::false];
            yield '(Countable&Traversable)|string' => ['(\Countable&\Traversable)|string', types::union(
                types::intersection(types::object(\Countable::class), types::object(\Traversable::class)),
                types::string,
            )];
        }
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function callableType(): \Generator
    {
        yield 'callable' => ['callable', types::callable()];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function voidType(): \Generator
    {
        yield 'void' => ['void', types::void];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function neverType(): \Generator
    {
        yield 'never' => ['never', types::never];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function staticType(): \Generator
    {
        yield 'static' => ['static', types::static(Main::class)];
    }

    private function locateCode(string $code): ClassLocator
    {
        return new ClassLocatorChain(array_map(
            static fn (string $class): SingleClassLocator => new SingleClassLocator($class, new Source('<?php '.$code)),
            [Main::class, Base::class],
        ));
    }
}
