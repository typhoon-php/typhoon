<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\NameContext\AnonymousClassName;

#[CoversNothing]
final class ReflectorCompatibilityTest extends TestCase
{
    private const CLASSES = __DIR__ . '/ReflectorCompatibility/classes.php';
    private const READONLY_CLASSES = __DIR__ . '/ReflectorCompatibility/readonly_classes.php';

    /**
     * @return \Generator<string, list<ClassLocator>>
     */
    public static function classLocators(): \Generator
    {
        yield 'TyphoonReflector::defaultClassLocators()' => TyphoonReflector::defaultClassLocators();
        yield 'No locators' => [];
    }

    /**
     * @return \Generator<string, array{list<ClassLocator>, string}>
     */
    public static function classes(): \Generator
    {
        require_once self::CLASSES;

        $anonymousNames = AnonymousClassName::findDeclared(file: self::CLASSES);

        foreach (self::classLocators() as $classLocatorsName => $classLocators) {
            foreach (NameCollector::collect(self::CLASSES)->classes as $class) {
                yield $class . ' using ' . $classLocatorsName => [$classLocators, $class];
            }

            foreach ($anonymousNames as $anonymousName) {
                yield 'anonymous at line ' . $anonymousName->line . ' using ' . $classLocatorsName => [$classLocators, $anonymousName->toString()];
            }
        }
    }

    /**
     * @return \Generator<string, array{list<ClassLocator>, string}>
     */
    public static function readonlyClasses(): \Generator
    {
        if (\PHP_VERSION_ID >= 80200) {
            require_once self::READONLY_CLASSES;
        }

        foreach (self::classLocators() as $classLocatorsName => $classLocators) {
            foreach (NameCollector::collect(self::READONLY_CLASSES)->classes as $class) {
                yield $class . ' using ' . $classLocatorsName => [$classLocators, $class];
            }
        }
    }

    /**
     * @param list<ClassLocator> $classLocators
     */
    #[DataProvider('classes')]
    public function testItReflectsClassesCompatibly(array $classLocators, string $class): void
    {
        $reflector = TyphoonReflector::build(classLocators: $classLocators);
        /** @psalm-suppress ArgumentTypeCoercion */
        $native = new \ReflectionClass($class);

        $typhoon = $reflector->reflectClass($class);

        $this->assertClassEquals($native, $typhoon);
    }

    /**
     * @param ?list<ClassLocator> $classLocators
     */
    #[RequiresPhp('>=8.2')]
    #[DataProvider('readonlyClasses')]
    public function testItReflectsReadonlyClasses(?array $classLocators, string $class): void
    {
        $reflector = TyphoonReflector::build(classLocators: $classLocators);
        /** @psalm-suppress ArgumentTypeCoercion */
        $native = new \ReflectionClass($class);

        $typhoon = $reflector->reflectClass($class);

        /** @psalm-suppress UnusedPsalmSuppress, MixedArgument, UndefinedMethod */
        self::assertSame($native->isReadOnly(), $typhoon->isReadOnly());
    }

    private function assertClassEquals(\ReflectionClass $native, ClassReflection $typhoon): void
    {
        self::assertSame($this->resolveNativeClassName($native->name), $typhoon->name, 'class.name');
        self::assertSame($this->resolveNativeClassName($native->getName()), $typhoon->getName(), 'class.getName()');
        self::assertSame($this->resolveNativeClassName($native->getShortName()), $typhoon->getShortName(), 'class.getShortName()');
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), 'class.getAttributes()');
        // self::assertSame($native->getConstant(), $typhoon->getConstant(), 'class.getConstant()');
        // self::assertSame($native->getConstants(), $typhoon->getConstants(), 'class.getConstants()');
        self::assertSame($native->getDocComment() ?: null, $typhoon->getDocComment(), 'class.getDocComment()');
        self::assertSame($native->getEndLine(), $typhoon->getEndLine(), 'class.getEndLine()');
        // self::assertSame($native->getExtension(), $typhoon->getExtension(), 'class.getExtension()');
        self::assertSame($native->getExtensionName() ?: null, $typhoon->getExtensionName(), 'class.getExtensionName()');
        self::assertSame($native->getFileName(), $typhoon->getFileName(), 'class.getFileName()');
        self::assertSame($native->getInterfaceNames(), $typhoon->getInterfaceNames(), 'class.getInterfaceNames()');
        self::assertSameNames($native->getInterfaces(), $typhoon->getInterfaces(), 'class.getInterfaces().name');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), 'class.getModifiers()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), 'class.getNamespaceName()');
        self::assertSame($native->getParentClass()->name, $typhoon->getParentClassName(), 'class.getParentClassName()');
        // self::assertSame($native->getParentClass(), $typhoon->getParentClass(), 'class.getParentClass()');
        // self::assertSame($native->getReflectionConstant(), $typhoon->getReflectionConstant(), 'class.getReflectionConstant()');
        // self::assertSame($native->getReflectionConstants(), $typhoon->getReflectionConstants(), 'class.getReflectionConstants()');
        self::assertSame($native->getStartLine(), $typhoon->getStartLine(), 'class.getStartLine()');
        // self::assertSame($native->getTraitNames(), $typhoon->getTraitNames(), 'class.getTraitNames()');
        // self::assertSame($native->getTraits(), $typhoon->getTraits(), 'class.getTraits()');
        // self::assertSame($native->hasConstant(), $typhoon->hasConstant(), 'class.hasConstant()');
        // self::assertSame($native->implementsInterface(), $typhoon->implementsInterface(), 'class.implementsInterface()');
        self::assertSame($native->inNamespace(), $typhoon->inNamespace(), 'class.inNamespace()');
        self::assertSame($native->isAbstract(), $typhoon->isAbstract(), 'class.isAbstract()');
        self::assertSame($native->isAnonymous(), $typhoon->isAnonymous(), 'class.isAnonymous()');
        self::assertSame($native->isCloneable(), $typhoon->isCloneable(), 'class.isCloneable()');
        self::assertSame($native->isEnum(), $typhoon->isEnum(), 'class.isEnum()');
        self::assertSame($native->isFinal(), $typhoon->isFinal(), 'class.isFinal()');
        // self::assertSame($native->isInstance(), $typhoon->isInstance(), 'class.isInstance()');
        self::assertSame($native->isInstantiable(), $typhoon->isInstantiable(), 'class.isInstantiable()');
        self::assertSame($native->isInterface(), $typhoon->isInterface(), 'class.isInterface()');
        self::assertSame($native->isInternal(), $typhoon->isInternal(), 'class.isInternal()');
        self::assertSame($native->isIterable(), $typhoon->isIterable(), 'class.isIterable()');
        // self::assertSame($native->isSubclassOf(), $typhoon->isSubclassOf(), 'class.isSubclassOf()');
        self::assertSame($native->isTrait(), $typhoon->isTrait(), 'class.isTrait()');
        self::assertSame($native->isUserDefined(), $typhoon->isUserDefined(), 'class.isUserDefined()');

        if ($native->isInstantiable() && !$native->isAnonymous()) {
            // self::assertEquals($native->newInstance(), $typhoon->newInstance(), 'class.newInstance()');
            // self::assertEquals($native->newInstanceArgs(), $typhoon->newInstanceArgs(), 'class.newInstanceArgs()');
            self::assertEquals($native->newInstanceWithoutConstructor(), $typhoon->newInstanceWithoutConstructor(), 'class.newInstanceWithoutConstructor()');
        }

        $this->assertSameNames($native->getProperties(), $typhoon->getProperties(), 'class.getProperties().name');
        $this->assertSameNames($native->getProperties(\ReflectionProperty::IS_PUBLIC), $typhoon->getProperties(PropertyReflection::IS_PUBLIC), 'class.getProperties(IS_PUBLIC).name');
        $this->assertSameNames($native->getProperties(\ReflectionProperty::IS_PROTECTED), $typhoon->getProperties(PropertyReflection::IS_PROTECTED), 'class.getProperties(IS_PROTECTED).name');
        $this->assertSameNames($native->getProperties(\ReflectionProperty::IS_PRIVATE), $typhoon->getProperties(PropertyReflection::IS_PRIVATE), 'class.getProperties(IS_PRIVATE).name');
        $this->assertSameNames($native->getProperties(\ReflectionProperty::IS_STATIC), $typhoon->getProperties(PropertyReflection::IS_STATIC), 'class.getProperties(IS_STATIC).name');
        $this->assertSameNames($native->getProperties(\ReflectionProperty::IS_READONLY), $typhoon->getProperties(PropertyReflection::IS_READONLY), 'class.getProperties(IS_READONLY).name');

        foreach ($native->getProperties() as $nativeProperty) {
            self::assertTrue($typhoon->hasProperty($nativeProperty->name), "class.hasProperty({$nativeProperty->name})");
            $this->assertPropertyEquals($nativeProperty, $typhoon->getProperty($nativeProperty->name), "class.getProperty({$nativeProperty->name})");
        }

        $this->assertSameNames($native->getMethods(), $typhoon->getMethods(), 'class.getMethods().name');
        $this->assertSameNames($native->getMethods(\ReflectionMethod::IS_FINAL), $typhoon->getMethods(MethodReflection::IS_FINAL), 'class.getMethods(IS_FINAL).name');
        $this->assertSameNames($native->getMethods(\ReflectionMethod::IS_ABSTRACT), $typhoon->getMethods(MethodReflection::IS_ABSTRACT), 'class.getMethods(IS_ABSTRACT).name');
        $this->assertSameNames($native->getMethods(\ReflectionMethod::IS_PUBLIC), $typhoon->getMethods(MethodReflection::IS_PUBLIC), 'class.getMethods(IS_PUBLIC).name');
        $this->assertSameNames($native->getMethods(\ReflectionMethod::IS_PROTECTED), $typhoon->getMethods(MethodReflection::IS_PROTECTED), 'class.getMethods(IS_PROTECTED).name');
        $this->assertSameNames($native->getMethods(\ReflectionMethod::IS_PRIVATE), $typhoon->getMethods(MethodReflection::IS_PRIVATE), 'class.getMethods(IS_PRIVATE).name');
        $this->assertSameNames($native->getMethods(\ReflectionMethod::IS_STATIC), $typhoon->getMethods(MethodReflection::IS_STATIC), 'class.getMethods(IS_STATIC).name');

        if ($native->hasMethod('__construct')) {
            self::assertSame($typhoon->getMethod('__construct'), $typhoon->getConstructor(), 'class.getConstructor()');
        } else {
            self::assertNull($typhoon->getConstructor(), 'class.getConstructor()');
        }

        foreach ($native->getMethods() as $nativeMethod) {
            self::assertTrue($typhoon->hasMethod($nativeMethod->name), "hasMethod({$nativeMethod->name})");
            $this->assertMethodEquals($nativeMethod, $typhoon->getMethod($nativeMethod->name), "getMethod({$nativeMethod->name})");
        }
    }

    private function assertPropertyEquals(\ReflectionProperty $native, PropertyReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($this->resolveNativeClassName($native->class), $typhoon->class, $messagePrefix . '.class');

        if (!$native->getDeclaringClass()->isAnonymous()) {
            // TODO: cannot create native reflection for anonymous with customized name
            self::assertSame($native->getDefaultValue(), $typhoon->getDefaultValue(), $messagePrefix . '.getDefaultValue()');
        }

        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix . '.getAttributes()');
        self::assertSame($this->resolveNativeClassName($native->getDeclaringClass()->name), $typhoon->getDeclaringClass()->name, $messagePrefix . '.getDeclaringClass()');
        self::assertSame($native->getDocComment() ?: null, $typhoon->getDocComment(), $messagePrefix . '.getDocComment()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . '.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        // self::assertSame($native->getValue(), $typhoon->getValue(), $messagePrefix . '.getValue()');
        self::assertSame($native->hasDefaultValue(), $typhoon->hasDefaultValue(), $messagePrefix . '.hasDefaultValue()');
        // self::assertSame($native->isDefault(), $typhoon->isDefault(), $messagePrefix . '.isDefault()');
        // self::assertSame($native->isInitialized(), $typhoon->isInitialized(), $messagePrefix . '.isInitialized()');
        self::assertSame($native->isPrivate(), $typhoon->isPrivate(), $messagePrefix . '.isPrivate()');
        self::assertSame($native->isPromoted(), $typhoon->isPromoted(), $messagePrefix . '.isPromoted()');
        self::assertSame($native->isProtected(), $typhoon->isProtected(), $messagePrefix . '.isProtected()');
        self::assertSame($native->isPublic(), $typhoon->isPublic(), $messagePrefix . '.isPublic()');
        self::assertSame($native->isReadOnly(), $typhoon->isReadOnly(), $messagePrefix . '.isReadOnly()');
        self::assertSame($native->isStatic(), $typhoon->isStatic(), $messagePrefix . '.isStatic()');
        // self::assertSame($native->setValue(), $typhoon->setValue(), $messagePrefix . '.setValue()');
    }

    private function assertMethodEquals(\ReflectionMethod $native, MethodReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($this->resolveNativeClassName($native->class), $typhoon->class, $messagePrefix . '.class');
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix . '.getAttributes()');
        // self::assertSame($native->getClosure(), $typhoon->getClosure(), $messagePrefix . '.getClosure()');
        // self::assertSame($native->getClosureCalledClass(), $typhoon->getClosureCalledClass(), $messagePrefix . '.getClosureCalledClass()');
        // self::assertSame($native->getClosureScopeClass(), $typhoon->getClosureScopeClass(), $messagePrefix . '.getClosureScopeClass()');
        // self::assertSame($native->getClosureThis(), $typhoon->getClosureThis(), $messagePrefix . '.getClosureThis()');
        // self::assertSame($native->getClosureUsedVariables(), $typhoon->getClosureUsedVariables(), $messagePrefix . '.getClosureUsedVariables()');
        self::assertSame($this->resolveNativeClassName($native->getDeclaringClass()->name), $typhoon->getDeclaringClass()->name, $messagePrefix . '.getDeclaringClass()');
        self::assertSame($native->getDocComment() ?: null, $typhoon->getDocComment(), $messagePrefix . '.getDocComment()');
        // self::assertSame($native->getEndLine(), $typhoon->getEndLine(), $messagePrefix . '.getEndLine()');
        // self::assertSame($native->getExtension(), $typhoon->getExtension(), $messagePrefix . '.getExtension()');
        self::assertSame($native->getExtensionName() ?: null, $typhoon->getExtensionName(), $messagePrefix . '.getExtensionName()');
        // self::assertSame($native->getFileName(), $typhoon->getFileName(), $messagePrefix . '.getFileName()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . '.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), $messagePrefix . '.getNamespaceName()');
        self::assertSame($native->getNumberOfParameters(), $typhoon->getNumberOfParameters(), $messagePrefix . '.getNumberOfParameters()');
        self::assertSame($native->getNumberOfRequiredParameters(), $typhoon->getNumberOfRequiredParameters(), $messagePrefix . '.getNumberOfRequiredParameters()');
        // self::assertSame($native->getPrototype(), $typhoon->getPrototype(), $messagePrefix . '.getPrototype()');
        self::assertSame($native->getShortName(), $typhoon->getShortName(), $messagePrefix . '.getShortName()');
        // self::assertSame($native->getStartLine(), $typhoon->getStartLine(), $messagePrefix . '.getStartLine()');
        // self::assertSame($native->getStaticVariables(), $typhoon->getStaticVariables(), $messagePrefix . '.getStaticVariables()');
        // self::assertSame($native->hasPrototype(), $typhoon->hasPrototype(), $messagePrefix . '.hasPrototype()');
        self::assertSame($native->inNamespace(), $typhoon->inNamespace(), $messagePrefix . '.inNamespace()');
        // self::assertSame($native->invoke(), $typhoon->invoke(), $messagePrefix . '.invoke()');
        // self::assertSame($native->invokeArgs(), $typhoon->invokeArgs(), $messagePrefix . '.invokeArgs()');
        self::assertSame($native->isAbstract(), $typhoon->isAbstract(), $messagePrefix . '.isAbstract()');
        self::assertSame($native->isClosure(), $typhoon->isClosure(), $messagePrefix . '.isClosure()');
        self::assertSame($native->isConstructor(), $typhoon->isConstructor(), $messagePrefix . '.isConstructor()');
        self::assertSame($native->isDeprecated(), $typhoon->isDeprecated(), $messagePrefix . '.isDeprecated()');
        self::assertSame($native->isDestructor(), $typhoon->isDestructor(), $messagePrefix . '.isDestructor()');
        self::assertSame($native->isFinal(), $typhoon->isFinal(), $messagePrefix . '.isFinal()');
        self::assertSame($native->isGenerator(), $typhoon->isGenerator(), $messagePrefix . '.isGenerator()');
        self::assertSame($native->isInternal(), $typhoon->isInternal(), $messagePrefix . '.isInternal()');
        self::assertSame($native->isPrivate(), $typhoon->isPrivate(), $messagePrefix . '.isPrivate()');
        self::assertSame($native->isProtected(), $typhoon->isProtected(), $messagePrefix . '.isProtected()');
        self::assertSame($native->isPublic(), $typhoon->isPublic(), $messagePrefix . '.isPublic()');
        self::assertSame($native->isStatic(), $typhoon->isStatic(), $messagePrefix . '.isStatic()');
        self::assertSame($native->isUserDefined(), $typhoon->isUserDefined(), $messagePrefix . '.isUserDefined()');
        self::assertSame($native->isVariadic(), $typhoon->isVariadic(), $messagePrefix . '.isVariadic()');
        self::assertSame($native->returnsReference(), $typhoon->returnsReference(), $messagePrefix . '.returnsReference()');

        $this->assertSameNames($native->getParameters(), $typhoon->getParameters(), 'getParameters().name');

        foreach ($native->getParameters() as $position => $nativeParameter) {
            $name = $nativeParameter->name;
            self::assertTrue($typhoon->hasParameterWithName($name), $messagePrefix . ".hasParameter({$name})");
            self::assertTrue($typhoon->hasParameterWithPosition($position), $messagePrefix . ".hasParameter({$position})");
            self::assertSame($typhoon->getParameterByPosition($position), $typhoon->getParameterByName($name), $messagePrefix . ".getParameter({$position}) === getParameter({$name})");
            $this->assertParameterEquals($nativeParameter, $typhoon->getParameters()[$position], $messagePrefix . ".getParameter()[{$position}]");
        }
    }

    private function assertParameterEquals(\ReflectionParameter $native, ParameterReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->canBePassedByValue(), $typhoon->canBePassedByValue(), $messagePrefix . '.canBePassedByValue()');
        // self::assertSame($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix . '.getAttributes()');
        self::assertSame($this->resolveNativeClassName($native->getDeclaringClass()?->name), $typhoon->getDeclaringClass()?->name, $messagePrefix . '.getDeclaringClass()');
        self::assertSame($native->getDeclaringFunction()->name, $typhoon->getDeclaringFunction()->name, $messagePrefix . '.getDeclaringFunction()');
        // self::assertSame($native->getDefaultValueConstantName(), $typhoon->getDefaultValueConstantName(), $messagePrefix . '.getDefaultValueConstantName()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertSame($native->getPosition(), $typhoon->getPosition(), $messagePrefix . '.getPosition()');
        self::assertSame($native->isDefaultValueAvailable(), $typhoon->isDefaultValueAvailable(), $messagePrefix . '.isDefaultValueAvailable()');
        if ($native->isDefaultValueAvailable()) {
            self::assertEquals($native->getDefaultValue(), $typhoon->getDefaultValue(), $messagePrefix . '.getDefaultValue()');
        }
        // self::assertSame($native->isDefaultValueConstant(), $typhoon->isDefaultValueConstant(), $messagePrefix . '.isDefaultValueConstant()');
        self::assertSame($native->isOptional(), $typhoon->isOptional(), $messagePrefix . '.isOptional()');
        self::assertSame($native->isPassedByReference(), $typhoon->isPassedByReference(), $messagePrefix . '.isPassedByReference()');
        self::assertSame($native->isPromoted(), $typhoon->isPromoted(), $messagePrefix . '.isPromoted()');
        self::assertSame($native->isVariadic(), $typhoon->isVariadic(), $messagePrefix . '.isVariadic()');
    }

    /**
     * @param array<\ReflectionClass|\ReflectionProperty|\ReflectionMethod|\ReflectionParameter> $nativeReflections
     * @param array<ClassReflection|PropertyReflection|MethodReflection|ParameterReflection> $typhoonReflections
     */
    private function assertSameNames(array $nativeReflections, array $typhoonReflections, string $message): void
    {
        self::assertSame(
            array_map($this->resolveNativeClassName(...), array_column($nativeReflections, 'name')),
            array_column($typhoonReflections, 'name'),
            $message,
        );
    }

    /**
     * @return ($name is null ? null : string)
     */
    private function resolveNativeClassName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return AnonymousClassName::normalizeName($name);
    }
}
