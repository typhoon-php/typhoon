<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Type\ArrayT;
use ExtendedTypeSystem\Type\BoolT;
use ExtendedTypeSystem\Type\CallableT;
use ExtendedTypeSystem\Type\FalseT;
use ExtendedTypeSystem\Type\FloatT;
use ExtendedTypeSystem\Type\IntersectionT;
use ExtendedTypeSystem\Type\IntT;
use ExtendedTypeSystem\Type\IterableT;
use ExtendedTypeSystem\Type\MixedT;
use ExtendedTypeSystem\Type\NamedObjectT;
use ExtendedTypeSystem\Type\NeverT;
use ExtendedTypeSystem\Type\NullableT;
use ExtendedTypeSystem\Type\NullT;
use ExtendedTypeSystem\Type\ObjectT;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\StringT;
use ExtendedTypeSystem\Type\TrueT;
use ExtendedTypeSystem\Type\UnionT;
use ExtendedTypeSystem\Type\VoidT;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use function PHPUnit\Framework\assertEquals;

/**
 * @internal
 * @covers \ExtendedTypeSystem\Metadata\ClassMetadata
 * @covers \ExtendedTypeSystem\Metadata\FromStringMetadata
 * @covers \ExtendedTypeSystem\Metadata\FunctionMetadata
 * @covers \ExtendedTypeSystem\Metadata\Metadata
 * @covers \ExtendedTypeSystem\Metadata\MetadataFactory
 * @covers \ExtendedTypeSystem\Metadata\MethodMetadata
 * @covers \ExtendedTypeSystem\Metadata\PropertyMetadata
 * @covers \ExtendedTypeSystem\NameResolution\NameResolver
 * @covers \ExtendedTypeSystem\NameResolution\NameResolverFactory
 * @covers \ExtendedTypeSystem\Parser\PHPDocParser
 * @covers \ExtendedTypeSystem\Parser\PHPDocTags
 * @covers \ExtendedTypeSystem\TypeReflector
 * @group unit
 */
final class TypeReflectorTest extends TestCase
{
    private const STUBS_DIR = __DIR__.'/../var/test_stubs/PHPDocTypeReflectorTest';

    public static function setUpBeforeClass(): void
    {
        (new Filesystem())->remove(self::STUBS_DIR);
    }

    public static function tearDownAfterClass(): void
    {
        (new Filesystem())->remove(self::STUBS_DIR);
    }

    /**
     * @return callable-string
     */
    private static function generateFunctionName(): string
    {
        /** @var callable-string */
        return uniqid('function_');
    }

    /**
     * @return class-string
     */
    private static function generateClassName(): string
    {
        /** @var class-string */
        return uniqid('class_');
    }

    private static function require(string $code): void
    {
        $file = self::STUBS_DIR.'/'.uniqid(more_entropy: true).'.php';
        (new Filesystem())->dumpFile($file, "<?php\n\n".$code);

        /** @psalm-suppress UnresolvableInclude */
        require_once $file;
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     */
    public function testItReflectsNativeFunctionParameterType(string $type, ?Type $expectedType): void
    {
        $function = self::generateFunctionName();
        self::require(
            <<<PHP
                function {$function} ({$type} \$test): void {}
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectFunctionParameterType($function, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     * @dataProvider voidType
     * @dataProvider neverType
     */
    public function testItReflectsNativeFunctionReturnType(string $type, ?Type $expectedType): void
    {
        $typeDeclaration = $type === '' ? '' : ': '.$type;
        $function = self::generateFunctionName();
        self::require(
            <<<PHP
                function {$function} (){$typeDeclaration} {}
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectFunctionReturnType($function);

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativePropertyType(string $type, ?Type $expectedType): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public {$type} \$test;
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativePropertySelfType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public self \$test;
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsNativePropertyParentType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    public parent \$test;
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativePropertyInheritedParentType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    public parent \$test;
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsNativePromotedPropertyType(string $type, ?Type $expectedType): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public function __construct(public {$type} \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativePromotedPropertySelfType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public function __construct(public self \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsNativePromotedPropertyParentType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    public function __construct(public parent \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativePromotedPropertyInheritedParentType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    public function __construct(public parent \$test) {}
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     */
    public function testItReflectsNativeMethodParameterType(string $type, ?Type $expectedType): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public function test({$type} \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativeMethodSelfParameterType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public function test(self \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsNativeMethodParentParameterType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    public function test(parent \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativeMethodInheritedParentParameterType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    public function test(parent \$test) {}
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     */
    public function testItReflectsNativeMethodReturnType(string $type, ?Type $expectedType): void
    {
        $typeDeclaration = $type === '' ? '' : ': '.$type;
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public function test(){$typeDeclaration} {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsNativeMethodSelfReturnType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public function test(): self {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsNativeMethodParentReturnType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    public function test(): parent {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativeMethodInheritedParentReturnType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    public function test(): parent {}
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsNativeMethodStaticReturnType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    public function test(): static {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new StaticT($class), $reflectedType);
    }

    public function testItReflectsNativeMethodInheritedStaticReturnType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} {
                    public function test(): static {}
                }
                class {$class} extends {$baseClass} {}
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new StaticT($baseClass), $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     * @dataProvider voidType
     * @dataProvider neverType
     */
    public function testItReflectsTypeFromString(string $type, ?Type $expectedType): void
    {
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectTypeFromString($type);

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsSelfTypeFromString(): void
    {
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectTypeFromString('self', self::class);

        assertEquals(new NamedObjectT(self::class), $reflectedType);
    }

    public function testItReflectsParentTypeFromString(): void
    {
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectTypeFromString('parent', self::class);

        assertEquals(new NamedObjectT(parent::class), $reflectedType);
    }

    public function testItReflectsStaticTypeFromString(): void
    {
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectTypeFromString('static', self::class);

        assertEquals(new NamedObjectT(static::class), $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     */
    public function testItReflectsFunctionParameterType(string $type, ?Type $expectedType): void
    {
        $function = self::generateFunctionName();
        self::require(
            <<<PHP
                /** @param {$type} \$test */
                function {$function} (\$test): void {}
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectFunctionParameterType($function, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     * @dataProvider voidType
     * @dataProvider neverType
     */
    public function testItReflectsFunctionReturnType(string $type, ?Type $expectedType): void
    {
        $function = self::generateFunctionName();
        self::require(
            <<<PHP
                /** @return {$type} */
                function {$function} () {}
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectFunctionReturnType($function);

        assertEquals($expectedType, $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsPropertyType(string $type, ?Type $expectedType): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @var {$type} */
                    public {$type} \$test;
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsPropertySelfType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @var self */
                    public \$test;
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsPropertyParentType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    /** @var parent */
                    public \$test;
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsPropertyInheritedParentType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    /** @var parent */
                    public \$test;
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     */
    public function testItReflectsPromotedPropertyType(string $type, ?Type $expectedType): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @param {$type} \$test */
                    public function __construct(public \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsPromotedPropertySelfType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @param self \$test */
                    public function __construct(public \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsPromotedPropertyParentType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    /** @param parent \$test */
                    public function __construct(public \$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsPromotedPropertyInheritedParentType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    /** @param parent \$test */
                    public function __construct(public \$test) {}
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectPropertyType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     */
    public function testItReflectsMethodParameterType(string $type, ?Type $expectedType): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @param {$type} \$test */
                    public function test(\$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsMethodSelfParameterType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @param self \$test */
                    public function test(\$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsMethodParentParameterType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    /** @param parent \$test */
                    public function test(\$test) {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsMethodInheritedParentParameterType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    /** @param parent \$test */
                    public function test(\$test) {}
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodParameterType($class, 'test', 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    /**
     * @dataProvider types
     * @dataProvider callableType
     */
    public function testItReflectsMethodReturnType(string $type, ?Type $expectedType): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @return {$type} */
                    public function test() {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals($expectedType, $reflectedType);
    }

    public function testItReflectsMethodSelfReturnType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @return self */
                    public function test() {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new NamedObjectT($class), $reflectedType);
    }

    public function testItReflectsMethodParentReturnType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} extends \\ArrayObject {
                    /** @return parent */
                    public function test() {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsMethodInheritedParentReturnType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} extends \\ArrayObject {
                    /** @return parent */
                    public function test() {}
                }
                class {$class} extends {$baseClass} {
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new NamedObjectT(\ArrayObject::class), $reflectedType);
    }

    public function testItReflectsMethodStaticReturnType(): void
    {
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$class} {
                    /** @return static */
                    public function test() {}
                }
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new StaticT($class), $reflectedType);
    }

    public function testItReflectsMethodInheritedStaticReturnType(): void
    {
        $baseClass = self::generateClassName();
        $class = self::generateClassName();
        self::require(
            <<<PHP
                class {$baseClass} {
                    /** @return static */
                    public function test() {}
                }
                class {$class} extends {$baseClass} {}
                PHP,
        );
        $reflector = new TypeReflector();

        $reflectedType = $reflector->reflectMethodReturnType($class, 'test');

        assertEquals(new StaticT($baseClass), $reflectedType);
    }

    /**
     * @return \Generator<string, array{string, ?Type}>
     */
    public function types(): \Generator
    {
        yield 'no type' => ['', null];
        yield 'bool' => ['bool', new BoolT()];
        yield 'int' => ['int', new IntT()];
        yield 'float' => ['float', new FloatT()];
        yield 'string' => ['string', new StringT()];
        yield 'array' => ['array', new ArrayT()];
        yield 'iterable' => ['iterable', new IterableT()];
        yield 'object' => ['object', new ObjectT()];
        yield 'mixed' => ['mixed', new MixedT()];
        yield '\Closure' => ['\Closure', new NamedObjectT(\Closure::class)];
        yield 'string|int|null' => ['string|int|null', new UnionT(new StringT(), new IntT(), new NullT())];
        yield 'string|false' => ['string|false', new UnionT(new StringT(), new FalseT())];
        yield '\Countable&\Traversable' => ['\Countable&\Traversable', new IntersectionT(new NamedObjectT(\Countable::class), new NamedObjectT(\Traversable::class))];
        yield '?int' => ['?int', new NullableT(new IntT())];

        if (\PHP_VERSION_ID >= 80200) {
            yield 'null' => ['null', new NullT()];
            yield 'true' => ['true', new TrueT()];
            yield 'false' => ['false', new FalseT()];
            yield '(\Countable&\Traversable)|string' => ['(\Countable&\Traversable)|string', new UnionT(
                new IntersectionT(new NamedObjectT(\Countable::class), new NamedObjectT(\Traversable::class)),
                new StringT(),
            )];
        }
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function callableType(): \Generator
    {
        yield 'callable' => ['callable', new CallableT()];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function voidType(): \Generator
    {
        yield 'void' => ['void', new VoidT()];
    }

    /**
     * @return \Generator<string, array{string, Type}>
     */
    public function neverType(): \Generator
    {
        yield 'never' => ['never', new NeverT()];
    }
}
