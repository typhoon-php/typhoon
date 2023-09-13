<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ReflectorCompatibilityTest extends TestCase
{
    private const CLASSES = __DIR__ . '/ReflectorCompatibility/classes.php';
    private const READONLY_CLASSES = __DIR__ . '/ReflectorCompatibility/readonly_classes.php';

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function classes(): \Generator
    {
        foreach (NameCollector::collect(self::CLASSES)->classes as $class) {
            yield $class => [self::CLASSES, $class];
        }
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function readonlyClasses(): \Generator
    {
        foreach (NameCollector::collect(self::READONLY_CLASSES)->classes as $class) {
            yield $class => [self::READONLY_CLASSES, $class];
        }
    }

    #[DataProvider('classes')]
    public function testItReflectsClassesCompatibly(string $file, string $class): void
    {
        include_once $file;
        $reflector = Reflector::build(cache: false);
        /** @psalm-suppress ArgumentTypeCoercion */
        $native = new \ReflectionClass($class);

        $typhoon = $reflector->reflectClass($class);

        $this->assertClassEquals($native, $typhoon);
    }

    #[RequiresPhp('>=8.2')]
    #[DataProvider('readonlyClasses')]
    public function testItReflectsReadonlyClasses(string $file, string $class): void
    {
        include_once $file;
        $reflector = Reflector::build(cache: false);
        /** @psalm-suppress ArgumentTypeCoercion */
        $native = new \ReflectionClass($class);

        $typhoon = $reflector->reflectClass($class);

        /** @psalm-suppress UnusedPsalmSuppress, MixedArgument, UndefinedMethod */
        self::assertSame($native->isReadOnly(), $typhoon->isReadOnly());
    }

    private function assertClassEquals(\ReflectionClass $native, ClassReflection $typhoon): void
    {
        self::assertSame($native->name, $typhoon->name);
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), 'getAttributes()');
        // self::assertSame($native->getConstant(), $typhoon->getConstant(), 'getConstant()');
        // self::assertSame($native->getConstants(), $typhoon->getConstants(), 'getConstants()');
        self::assertSame($native->getDocComment() ?: null, $typhoon->getDocComment(), 'getDocComment()');
        self::assertSame($native->getEndLine(), $typhoon->getEndLine(), 'getEndLine()');
        // self::assertSame($native->getExtension(), $typhoon->getExtension(), 'getExtension()');
        self::assertSame($native->getExtensionName() ?: null, $typhoon->getExtensionName(), 'getExtensionName()');
        self::assertSame($native->getFileName(), $typhoon->getFileName(), 'getFileName()');
        self::assertSame($native->getInterfaceNames(), $typhoon->getInterfaceNames(), 'getInterfaceNames()');
        self::assertSame(array_column($native->getInterfaces(), 'name'), array_column($typhoon->getInterfaces(), 'name'), 'getInterfaces().name');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), 'getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), 'getName()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), 'getNamespaceName()');
        self::assertSame($native->getParentClass()->name, $typhoon->getParentClassName(), 'getParentClassName()');
        // self::assertSame($native->getParentClass(), $typhoon->getParentClass(), 'getParentClass()');
        // self::assertSame($native->getReflectionConstant(), $typhoon->getReflectionConstant(), 'getReflectionConstant()');
        // self::assertSame($native->getReflectionConstants(), $typhoon->getReflectionConstants(), 'getReflectionConstants()');
        self::assertSame($native->getShortName(), $typhoon->getShortName(), 'getShortName()');
        self::assertSame($native->getStartLine(), $typhoon->getStartLine(), 'getStartLine()');
        // self::assertSame($native->getTraitNames(), $typhoon->getTraitNames(), 'getTraitNames()');
        // self::assertSame($native->getTraits(), $typhoon->getTraits(), 'getTraits()');
        // self::assertSame($native->hasConstant(), $typhoon->hasConstant(), 'hasConstant()');
        // self::assertSame($native->implementsInterface(), $typhoon->implementsInterface(), 'implementsInterface()');
        self::assertSame($native->inNamespace(), $typhoon->inNamespace(), 'inNamespace()');
        self::assertSame($native->isAbstract(), $typhoon->isAbstract(), 'isAbstract()');
        self::assertSame($native->isAnonymous(), $typhoon->isAnonymous(), 'isAnonymous()');
        self::assertSame($native->isCloneable(), $typhoon->isCloneable(), 'isCloneable()');
        self::assertSame($native->isEnum(), $typhoon->isEnum(), 'isEnum()');
        self::assertSame($native->isFinal(), $typhoon->isFinal(), 'isFinal()');
        // self::assertSame($native->isInstance(), $typhoon->isInstance(), 'isInstance()');
        self::assertSame($native->isInstantiable(), $typhoon->isInstantiable(), 'isInstantiable()');
        self::assertSame($native->isInterface(), $typhoon->isInterface(), 'isInterface()');
        // self::assertSame($native->isInternal(), $typhoon->isInternal(), 'isInternal()');
        self::assertSame($native->isIterable(), $typhoon->isIterable(), 'isIterable()');
        // self::assertSame($native->isSubclassOf(), $typhoon->isSubclassOf(), 'isSubclassOf()');
        self::assertSame($native->isTrait(), $typhoon->isTrait(), 'isTrait()');
        // self::assertSame($native->isUserDefined(), $typhoon->isUserDefined(), 'isUserDefined()');

        if ($native->isInstantiable()) {
            // self::assertEquals($native->newInstance(), $typhoon->newInstance());
            // self::assertEquals($native->newInstanceArgs(), $typhoon->newInstanceArgs(), 'newInstanceArgs()');
            self::assertEquals($native->newInstanceWithoutConstructor(), $typhoon->newInstanceWithoutConstructor());
        }

        self::assertSame(
            array_column($native->getProperties(), 'name'),
            array_column($typhoon->getProperties(), 'name'),
            'getProperties().name',
        );
        self::assertSame(
            array_column($native->getProperties(PropertyReflection::IS_PUBLIC), 'name'),
            array_column($typhoon->getProperties(PropertyReflection::IS_PUBLIC), 'name'),
            'getProperties(IS_PUBLIC).name',
        );
        self::assertSame(
            array_column($native->getProperties(PropertyReflection::IS_READONLY), 'name'),
            array_column($typhoon->getProperties(PropertyReflection::IS_READONLY), 'name'),
            'getProperties(IS_READONLY).name',
        );

        foreach ($native->getProperties() as $nativeProperty) {
            self::assertTrue($typhoon->hasProperty($nativeProperty->name), "hasProperty({$nativeProperty->name}).");
            $this->assertPropertyEquals($nativeProperty, $typhoon->getProperty($nativeProperty->name), "getProperty({$nativeProperty->name}).");
        }

        self::assertSame(
            array_column($native->getMethods(), 'name'),
            array_column($typhoon->getMethods(), 'name'),
            'getMethods().name',
        );
        self::assertSame(
            array_column($native->getMethods(MethodReflection::IS_PUBLIC), 'name'),
            array_column($typhoon->getMethods(MethodReflection::IS_PUBLIC), 'name'),
            'getMethods(IS_PUBLIC).name',
        );
        self::assertSame(
            array_column($native->getMethods(MethodReflection::IS_ABSTRACT), 'name'),
            array_column($typhoon->getMethods(MethodReflection::IS_ABSTRACT), 'name'),
            'getMethods(IS_ABSTRACT).name',
        );

        if ($native->hasMethod('__construct')) {
            self::assertSame($typhoon->getMethod('__construct'), $typhoon->getConstructor(), 'getConstructor()');
        } else {
            self::assertNull($typhoon->getConstructor(), 'getConstructor()');
        }

        foreach ($native->getMethods() as $nativeMethod) {
            self::assertTrue($typhoon->hasMethod($nativeMethod->name), "hasMethod({$nativeMethod->name}).");
            $this->assertMethodEquals($nativeMethod, $typhoon->getMethod($nativeMethod->name), "getMethod({$nativeMethod->name}).");
        }
    }

    private function assertPropertyEquals(\ReflectionProperty $native, PropertyReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->class, $typhoon->class, $messagePrefix . 'class');
        self::assertSame($native->name, $typhoon->name, $messagePrefix . 'name');
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix.'getAttributes()');
        // self::assertSame($native->getDeclaringClass(), $typhoon->getDeclaringClass(), $messagePrefix.'getDeclaringClass()');
        self::assertSame($native->getDefaultValue(), $typhoon->getDefaultValue(), $messagePrefix . 'getDefaultValue()');
        self::assertSame($native->getDocComment() ?: null, $typhoon->getDocComment(), $messagePrefix . 'getDocComment()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . 'getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . 'getName()');
        // self::assertSame($native->getValue(), $typhoon->getValue(), $messagePrefix.'getValue()');
        self::assertSame($native->hasDefaultValue(), $typhoon->hasDefaultValue(), $messagePrefix . 'hasDefaultValue()');
        // self::assertSame($native->isDefault(), $typhoon->isDefault(), $messagePrefix.'isDefault()');
        // self::assertSame($native->isInitialized(), $typhoon->isInitialized(), $messagePrefix.'isInitialized()');
        self::assertSame($native->isPrivate(), $typhoon->isPrivate(), $messagePrefix . 'isPrivate()');
        self::assertSame($native->isPromoted(), $typhoon->isPromoted(), $messagePrefix . 'isPromoted()');
        self::assertSame($native->isProtected(), $typhoon->isProtected(), $messagePrefix . 'isProtected()');
        self::assertSame($native->isPublic(), $typhoon->isPublic(), $messagePrefix . 'isPublic()');
        self::assertSame($native->isReadOnly(), $typhoon->isReadOnly(), $messagePrefix . 'isReadOnly()');
        self::assertSame($native->isStatic(), $typhoon->isStatic(), $messagePrefix . 'isStatic()');
        // self::assertSame($native->setValue(), $typhoon->setValue(), $messagePrefix.'setValue()');
    }

    private function assertMethodEquals(\ReflectionMethod $native, MethodReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->class, $typhoon->class, $messagePrefix . 'class');
        self::assertSame($native->name, $typhoon->name, $messagePrefix . 'name');
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix.'getAttributes()');
        // self::assertSame($native->getClosure(), $typhoon->getClosure(), $messagePrefix.'getClosure()');
        // self::assertSame($native->getClosureCalledClass(), $typhoon->getClosureCalledClass(), $messagePrefix.'getClosureCalledClass()');
        // self::assertSame($native->getClosureScopeClass(), $typhoon->getClosureScopeClass(), $messagePrefix.'getClosureScopeClass()');
        // self::assertSame($native->getClosureThis(), $typhoon->getClosureThis(), $messagePrefix.'getClosureThis()');
        // self::assertSame($native->getClosureUsedVariables(), $typhoon->getClosureUsedVariables(), $messagePrefix.'getClosureUsedVariables()');
        // self::assertSame($native->getDeclaringClass(), $typhoon->getDeclaringClass(), $messagePrefix.'getDeclaringClass()');
        // self::assertSame($native->getDocComment() ?: null, $typhoon->getDocComment(), $messagePrefix.'getDocComment()');
        // self::assertSame($native->getEndLine(), $typhoon->getEndLine(), $messagePrefix.'getEndLine()');
        // self::assertSame($native->getExtension(), $typhoon->getExtension(), $messagePrefix.'getExtension()');
        self::assertSame($native->getExtensionName() ?: null, $typhoon->getExtensionName(), $messagePrefix . 'getExtensionName()');
        // self::assertSame($native->getFileName(), $typhoon->getFileName(), $messagePrefix.'getFileName()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . 'getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . 'getName()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), $messagePrefix . 'getNamespaceName()');
        self::assertSame($native->getNumberOfParameters(), $typhoon->getNumberOfParameters(), $messagePrefix . 'getNumberOfParameters()');
        self::assertSame($native->getNumberOfRequiredParameters(), $typhoon->getNumberOfRequiredParameters(), $messagePrefix . 'getNumberOfRequiredParameters()');
        // self::assertSame($native->getPrototype(), $typhoon->getPrototype(), $messagePrefix.'getPrototype()');
        self::assertSame($native->getShortName(), $typhoon->getShortName(), $messagePrefix . 'getShortName()');
        // self::assertSame($native->getStartLine(), $typhoon->getStartLine(), $messagePrefix.'getStartLine()');
        // self::assertSame($native->getStaticVariables(), $typhoon->getStaticVariables(), $messagePrefix.'getStaticVariables()');
        // self::assertSame($native->hasPrototype(), $typhoon->hasPrototype(), $messagePrefix.'hasPrototype()');
        self::assertSame($native->inNamespace(), $typhoon->inNamespace(), $messagePrefix . 'inNamespace()');
        // self::assertSame($native->invoke(), $typhoon->invoke(), $messagePrefix.'invoke()');
        // self::assertSame($native->invokeArgs(), $typhoon->invokeArgs(), $messagePrefix.'invokeArgs()');
        self::assertSame($native->isAbstract(), $typhoon->isAbstract(), $messagePrefix . 'isAbstract()');
        self::assertSame($native->isClosure(), $typhoon->isClosure(), $messagePrefix . 'isClosure()');
        self::assertSame($native->isConstructor(), $typhoon->isConstructor(), $messagePrefix . 'isConstructor()');
        // self::assertSame($native->isDeprecated(), $typhoon->isDeprecated(), $messagePrefix.'isDeprecated()');
        self::assertSame($native->isDestructor(), $typhoon->isDestructor(), $messagePrefix . 'isDestructor()');
        self::assertSame($native->isFinal(), $typhoon->isFinal(), $messagePrefix . 'isFinal()');
        self::assertSame($native->isGenerator(), $typhoon->isGenerator(), $messagePrefix . 'isGenerator()');
        // self::assertSame($native->isInternal(), $typhoon->isInternal(), $messagePrefix.'isInternal()');
        self::assertSame($native->isPrivate(), $typhoon->isPrivate(), $messagePrefix . 'isPrivate()');
        self::assertSame($native->isProtected(), $typhoon->isProtected(), $messagePrefix . 'isProtected()');
        self::assertSame($native->isPublic(), $typhoon->isPublic(), $messagePrefix . 'isPublic()');
        self::assertSame($native->isStatic(), $typhoon->isStatic(), $messagePrefix . 'isStatic()');
        // self::assertSame($native->isUserDefined(), $typhoon->isUserDefined(), $messagePrefix.'isUserDefined()');
        self::assertSame($native->isVariadic(), $typhoon->isVariadic(), $messagePrefix . 'isVariadic()');
        self::assertSame($native->returnsReference(), $typhoon->returnsReference(), $messagePrefix . 'returnsReference()');
        self::assertSame(array_column($native->getParameters(), 'name'), array_column($typhoon->getParameters(), 'name'), $messagePrefix . 'getParameters().name');

        foreach ($native->getParameters() as $index => $nativeParameter) {
            self::assertTrue($typhoon->hasParameter($nativeParameter->name), $messagePrefix . "hasParameter({$nativeParameter->name}).");
            self::assertTrue($typhoon->hasParameter($nativeParameter->getPosition()), $messagePrefix . "hasParameter({$nativeParameter->getPosition()}).");
            $this->assertParameterEquals($nativeParameter, $typhoon->getParameters()[$index], $messagePrefix . "getParameter()[{$index}].");
        }
    }

    private function assertParameterEquals(\ReflectionParameter $native, ParameterReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->name, $typhoon->name, $messagePrefix . 'name');
        self::assertSame($native->canBePassedByValue(), $typhoon->canBePassedByValue(), $messagePrefix . 'canBePassedByValue()');
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix.'getAttributes()');
        // self::assertSame($native->getDeclaringClass(), $typhoon->getDeclaringClass(), $messagePrefix.'getDeclaringClass()');
        // self::assertSame($native->getDeclaringFunction(), $typhoon->getDeclaringFunction(), $messagePrefix.'getDeclaringFunction()');
        // self::assertSame($native->getDefaultValueConstantName(), $typhoon->getDefaultValueConstantName(), $messagePrefix.'getDefaultValueConstantName()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . 'getName()');
        self::assertSame($native->getPosition(), $typhoon->getPosition(), $messagePrefix . 'getPosition()');
        self::assertSame($native->isDefaultValueAvailable(), $typhoon->isDefaultValueAvailable(), $messagePrefix . 'isDefaultValueAvailable()');
        if ($native->isDefaultValueAvailable()) {
            self::assertEquals($native->getDefaultValue(), $typhoon->getDefaultValue(), $messagePrefix . 'getDefaultValue()');
        }
        // self::assertSame($native->isDefaultValueConstant(), $typhoon->isDefaultValueConstant(), $messagePrefix.'isDefaultValueConstant()');
        self::assertSame($native->isOptional(), $typhoon->isOptional(), $messagePrefix . 'isOptional()');
        self::assertSame($native->isPassedByReference(), $typhoon->isPassedByReference(), $messagePrefix . 'isPassedByReference()');
        self::assertSame($native->isPromoted(), $typhoon->isPromoted(), $messagePrefix . 'isPromoted()');
        self::assertSame($native->isVariadic(), $typhoon->isVariadic(), $messagePrefix . 'isVariadic()');
    }
}
