<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Source\ClassLocatorChain;
use ExtendedTypeSystem\Source\SingleClassLocator;
use ExtendedTypeSystem\Source\Source;
use ExtendedTypeSystem\Stub\Base;
use ExtendedTypeSystem\Stub\Some;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

/**
 * @internal
 * @covers \ExtendedTypeSystem\TypeReflector
 * @group unit
 */
final class TypeReflectorTest extends TestCase
{
    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsNativePropertyType(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                public {$type} \$test;
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativePropertySelfType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                public self $test;
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsNativePropertyParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                public parent $test;
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativePropertyInheritedParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                public parent $test;
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsNativePromotedPropertyType(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                public function __construct(public {$type} \$test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativePromotedPropertySelfType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                public function __construct(public self $test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsNativePromotedPropertyParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                public function __construct(public parent $test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativePromotedPropertyInheritedParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                public function __construct(public parent $test) {}
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     * @dataProvider callableType
     */
    public function testItReflectsNativeMethodParameterType(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                public function test({$type} \$test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativeMethodSelfParameterType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                public function test(self $test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsNativeMethodParentParameterType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                public function test(parent $test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativeMethodInheritedParentParameterType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                public function test(parent $test) {}
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     * @dataProvider callableType
     * @dataProvider voidType
     * @dataProvider neverType
     */
    public function testItReflectsNativeMethodReturnType(string $type, Type $expectedType): void
    {
        $typeDeclaration = $type === '' ? '' : ': '.$type;
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                public function test(){$typeDeclaration} {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativeMethodSelfReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                public function test(): self {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsNativeMethodParentReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                public function test(): parent {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativeMethodInheritedParentReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                public function test(): parent {}
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativeMethodStaticReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                public function test(): static {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::static(Some::class), $reflectedType);
    }

    public function testItReflectsNativeMethodInheritedStaticReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base {
                public function test(): static {}
            }
            class Some extends Base {}
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::static(Base::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsPropertyType(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                /** @var {$type} */
                public {$type} \$test;
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsPropertySelfType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                /** @var self */
                public $test;
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsPropertyParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                /** @var parent */
                public $test;
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsPropertyInheritedParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @var parent */
                public $test;
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     */
    public function testItReflectsPromotedPropertyType(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                /** @param {$type} \$test */
                public function __construct(public \$test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsPromotedPropertySelfType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                /** @param self $test */
                public function __construct(public $test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsPromotedPropertyParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                /** @param parent $test */
                public function __construct(public $test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsPromotedPropertyInheritedParentType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @param parent $test */
                public function __construct(public $test) {}
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->propertyType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     * @dataProvider callableType
     */
    public function testItReflectsMethodParameterType(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                /** @param {$type} \$test */
                public function test(\$test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsMethodSelfParameterType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                /** @param self $test */
                public function test($test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsMethodParentParameterType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                /** @param parent $test */
                public function test($test) {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsMethodInheritedParentParameterType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @param parent $test */
                public function test($test) {}
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->parameterType('test');

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider nativeTypes
     * @dataProvider callableType
     * @dataProvider voidType
     * @dataProvider neverType
     */
    public function testItReflectsMethodReturnType(string $type, Type $expectedType): void
    {
        $code = <<<PHP
            namespace ExtendedTypeSystem\\Stub;
            class Some {
                /** @return {$type} */
                public function test() {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsMethodSelfReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                /** @return self */
                public function test() {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::object(Some::class), $reflectedType);
    }

    public function testItReflectsMethodParentReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some extends \ArrayObject {
                /** @return parent */
                public function test() {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsMethodInheritedParentReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base extends \ArrayObject {
                /** @return parent */
                public function test() {}
            }
            class Some extends Base {
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::object(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsMethodStaticReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Some {
                /** @return static */
                public function test() {}
            }
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

        assertEquals(types::static(Some::class), $reflectedType);
    }

    public function testItReflectsMethodInheritedStaticReturnType(): void
    {
        $code = <<<'PHP'
            namespace ExtendedTypeSystem\Stub;
            class Base {
                /** @return static */
                public function test() {}
            }
            class Some extends Base {}
            PHP;
        $reflector = new TypeReflector($this->classLocator($code, Base::class, Some::class));

        $reflectedType = $reflector->reflectClass(Some::class)->method('test')->returnType;

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
        yield '\Closure' => ['\Closure', types::object(\Closure::class)];
        yield 'string|int|null' => ['string|int|null', types::union(types::string, types::int, types::null)];
        yield 'string|false' => ['string|false', types::union(types::string, types::false)];
        yield '\Countable&\Traversable' => ['\Countable&\Traversable', types::intersection(types::object(\Countable::class), types::object(\Traversable::class))];
        yield '?int' => ['?int', types::nullable(types::int)];

        if (\PHP_VERSION_ID >= 80200) {
            yield 'null' => ['null', types::null];
            yield 'true' => ['true', types::true];
            yield 'false' => ['false', types::false];
            yield '(\Countable&\Traversable)|string' => ['(\Countable&\Traversable)|string', types::union(
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
     * @param class-string ...$classes
     */
    private function classLocator(string $code, string ...$classes): ClassLocator
    {
        return new ClassLocatorChain(array_map(
            static fn (string $class): SingleClassLocator => new SingleClassLocator($class, new Source('<?php '.$code)),
            $classes,
        ));
    }
}
