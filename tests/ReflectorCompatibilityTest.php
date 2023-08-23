<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ReflectorCompatibilityTest extends TestCase
{
    private const STUBS_DIR = __DIR__ . '/ReflectorCompatibility';
    private const CLASS_STUBS_FILE = self::STUBS_DIR . '/class.php';

    /**
     * @return array<string, array{string}>
     */
    public static function classes(): array
    {
        include_once self::CLASS_STUBS_FILE;

        $phpParser = new Php7(new Emulative());
        $nodes = $phpParser->parse(file_get_contents(self::CLASS_STUBS_FILE)) ?? [];
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $nameCollector = new NameCollector();
        $traverser->addVisitor($nameCollector);
        $traverser->traverse($nodes);

        return array_combine($nameCollector->classes, array_map(
            static fn (string $class): array => [$class],
            $nameCollector->classes,
        ));
    }

    #[DataProvider('classes')]
    public function testClass(string $class): void
    {
        $classReflection = Reflector::build(cache: false)->reflectClass($class);

        $this->assertClassEquals(new \ReflectionClass($class), $classReflection);
    }

    private function assertClassEquals(\ReflectionClass $native, ClassReflection $lib): void
    {
        self::assertSame($native->name, $lib->name);
        // self::assertSame($native->getAttributes(), $lib->getAttributes(), 'getAttributes()');
        // self::assertSame($native->getConstant(), $lib->getConstant(), 'getConstant()');
        // self::assertSame($native->getConstants(), $lib->getConstants(), 'getConstants()');
        self::assertSame($native->getDocComment() ?: null, $lib->getDocComment(), 'getDocComment()');
        self::assertSame($native->getEndLine(), $lib->getEndLine(), 'getEndLine()');
        // self::assertSame($native->getExtension(), $lib->getExtension(), 'getExtension()');
        self::assertSame($native->getExtensionName() ?: null, $lib->getExtensionName(), 'getExtensionName()');
        self::assertSame($native->getFileName(), $lib->getFileName(), 'getFileName()');
        self::assertSame($native->getInterfaceNames(), $lib->getInterfaceNames(), 'getInterfaceNames()');
        self::assertSame(array_column($native->getInterfaces(), 'name'), array_column($lib->getInterfaces(), 'name'), 'getInterfaces().name');
        self::assertSame($native->getModifiers(), $lib->getModifiers(), 'getModifiers()');
        self::assertSame($native->getName(), $lib->getName(), 'getName()');
        self::assertSame($native->getNamespaceName(), $lib->getNamespaceName(), 'getNamespaceName()');
        self::assertSame($native->getParentClass()->name, $lib->getParentClassName(), 'getParentClassName()');
        // self::assertSame($native->getParentClass(), $lib->getParentClass(), 'getParentClass()');
        // self::assertSame($native->getReflectionConstant(), $lib->getReflectionConstant(), 'getReflectionConstant()');
        // self::assertSame($native->getReflectionConstants(), $lib->getReflectionConstants(), 'getReflectionConstants()');
        self::assertSame($native->getShortName(), $lib->getShortName(), 'getShortName()');
        self::assertSame($native->getStartLine(), $lib->getStartLine(), 'getStartLine()');
        // self::assertSame($native->getTraitNames(), $lib->getTraitNames(), 'getTraitNames()');
        // self::assertSame($native->getTraits(), $lib->getTraits(), 'getTraits()');
        // self::assertSame($native->hasConstant(), $lib->hasConstant(), 'hasConstant()');
        // self::assertSame($native->implementsInterface(), $lib->implementsInterface(), 'implementsInterface()');
        self::assertSame($native->inNamespace(), $lib->inNamespace(), 'inNamespace()');
        self::assertSame($native->isAbstract(), $lib->isAbstract(), 'isAbstract()');
        self::assertSame($native->isAnonymous(), $lib->isAnonymous(), 'isAnonymous()');
        self::assertSame($native->isCloneable(), $lib->isCloneable(), 'isCloneable()');
        self::assertSame($native->isEnum(), $lib->isEnum(), 'isEnum()');
        self::assertSame($native->isFinal(), $lib->isFinal(), 'isFinal()');
        // self::assertSame($native->isInstance(), $lib->isInstance(), 'isInstance()');
        self::assertSame($native->isInstantiable(), $lib->isInstantiable(), 'isInstantiable()');
        self::assertSame($native->isInterface(), $lib->isInterface(), 'isInterface()');
        // self::assertSame($native->isInternal(), $lib->isInternal(), 'isInternal()');
        self::assertSame($native->isIterable(), $lib->isIterable(), 'isIterable()');

        if (\PHP_VERSION_ID >= 80200) {
            self::assertSame($native->isReadOnly(), $lib->isReadOnly(), 'isReadOnly()');
        }

        // self::assertSame($native->isSubclassOf(), $lib->isSubclassOf(), 'isSubclassOf()');
        self::assertSame($native->isTrait(), $lib->isTrait(), 'isTrait()');
        // self::assertSame($native->isUserDefined(), $lib->isUserDefined(), 'isUserDefined()');

        if ($native->isInstantiable()) {
            // self::assertEquals($native->newInstance(), $lib->newInstance());
            // self::assertEquals($native->newInstanceArgs(), $lib->newInstanceArgs(), 'newInstanceArgs()');
            self::assertEquals($native->newInstanceWithoutConstructor(), $lib->newInstanceWithoutConstructor());
        }

        self::assertSame(
            array_column($native->getProperties(), 'name'),
            array_column($lib->getProperties(), 'name'),
            'getProperties().name',
        );
        self::assertSame(
            array_column($native->getProperties(PropertyReflection::IS_PUBLIC), 'name'),
            array_column($lib->getProperties(PropertyReflection::IS_PUBLIC), 'name'),
            'getProperties(IS_PUBLIC).name',
        );
        self::assertSame(
            array_column($native->getProperties(PropertyReflection::IS_READONLY), 'name'),
            array_column($lib->getProperties(PropertyReflection::IS_READONLY), 'name'),
            'getProperties(IS_READONLY).name',
        );

        foreach ($native->getProperties() as $nativeProperty) {
            self::assertTrue($lib->hasProperty($nativeProperty->name), "hasProperty({$nativeProperty->name}).");
            $this->assertPropertyEquals($nativeProperty, $lib->getProperty($nativeProperty->name), "getProperty({$nativeProperty->name}).");
        }

        self::assertSame(
            array_column($native->getMethods(), 'name'),
            array_column($lib->getMethods(), 'name'),
            'getMethods().name',
        );
        self::assertSame(
            array_column($native->getMethods(MethodReflection::IS_PUBLIC), 'name'),
            array_column($lib->getMethods(MethodReflection::IS_PUBLIC), 'name'),
            'getMethods(IS_PUBLIC).name',
        );
        self::assertSame(
            array_column($native->getMethods(MethodReflection::IS_ABSTRACT), 'name'),
            array_column($lib->getMethods(MethodReflection::IS_ABSTRACT), 'name'),
            'getMethods(IS_ABSTRACT).name',
        );

        if ($native->hasMethod('__construct')) {
            self::assertSame($lib->getMethod('__construct'), $lib->getConstructor(), 'getConstructor()');
        } else {
            self::assertNull($lib->getConstructor(), 'getConstructor()');
        }

        foreach ($native->getMethods() as $nativeMethod) {
            self::assertTrue($lib->hasMethod($nativeMethod->name), "hasMethod({$nativeMethod->name}).");
            $this->assertMethodEquals($nativeMethod, $lib->getMethod($nativeMethod->name), "getMethod({$nativeMethod->name}).");
        }
    }

    private function assertPropertyEquals(\ReflectionProperty $native, PropertyReflection $lib, string $messagePrefix): void
    {
        self::assertSame($native->class, $lib->class, $messagePrefix . 'class');
        self::assertSame($native->name, $lib->name, $messagePrefix . 'name');
        // self::assertSame($native->getAttributes(), $lib->getAttributes(), $messagePrefix.'getAttributes()');
        // self::assertSame($native->getDeclaringClass(), $lib->getDeclaringClass(), $messagePrefix.'getDeclaringClass()');
        self::assertSame($native->getDefaultValue(), $lib->getDefaultValue(), $messagePrefix . 'getDefaultValue()');
        self::assertSame($native->getDocComment() ?: null, $lib->getDocComment(), $messagePrefix . 'getDocComment()');
        self::assertSame($native->getModifiers(), $lib->getModifiers(), $messagePrefix . 'getModifiers()');
        self::assertSame($native->getName(), $lib->getName(), $messagePrefix . 'getName()');
        // self::assertSame($native->getValue(), $lib->getValue(), $messagePrefix.'getValue()');
        self::assertSame($native->hasDefaultValue(), $lib->hasDefaultValue(), $messagePrefix . 'hasDefaultValue()');
        // self::assertSame($native->isDefault(), $lib->isDefault(), $messagePrefix.'isDefault()');
        // self::assertSame($native->isInitialized(), $lib->isInitialized(), $messagePrefix.'isInitialized()');
        self::assertSame($native->isPrivate(), $lib->isPrivate(), $messagePrefix . 'isPrivate()');
        self::assertSame($native->isPromoted(), $lib->isPromoted(), $messagePrefix . 'isPromoted()');
        self::assertSame($native->isProtected(), $lib->isProtected(), $messagePrefix . 'isProtected()');
        self::assertSame($native->isPublic(), $lib->isPublic(), $messagePrefix . 'isPublic()');
        self::assertSame($native->isReadOnly(), $lib->isReadOnly(), $messagePrefix . 'isReadOnly()');
        self::assertSame($native->isStatic(), $lib->isStatic(), $messagePrefix . 'isStatic()');
        // self::assertSame($native->setValue(), $lib->setValue(), $messagePrefix.'setValue()');
    }

    private function assertMethodEquals(\ReflectionMethod $native, MethodReflection $lib, string $messagePrefix): void
    {
        self::assertSame($native->class, $lib->class, $messagePrefix . 'class');
        self::assertSame($native->name, $lib->name, $messagePrefix . 'name');
        // self::assertSame($native->getAttributes(), $lib->getAttributes(), $messagePrefix.'getAttributes()');
        // self::assertSame($native->getClosure(), $lib->getClosure(), $messagePrefix.'getClosure()');
        // self::assertSame($native->getClosureCalledClass(), $lib->getClosureCalledClass(), $messagePrefix.'getClosureCalledClass()');
        // self::assertSame($native->getClosureScopeClass(), $lib->getClosureScopeClass(), $messagePrefix.'getClosureScopeClass()');
        // self::assertSame($native->getClosureThis(), $lib->getClosureThis(), $messagePrefix.'getClosureThis()');
        // self::assertSame($native->getClosureUsedVariables(), $lib->getClosureUsedVariables(), $messagePrefix.'getClosureUsedVariables()');
        // self::assertSame($native->getDeclaringClass(), $lib->getDeclaringClass(), $messagePrefix.'getDeclaringClass()');
        // self::assertSame($native->getDocComment() ?: null, $lib->getDocComment(), $messagePrefix.'getDocComment()');
        // self::assertSame($native->getEndLine(), $lib->getEndLine(), $messagePrefix.'getEndLine()');
        // self::assertSame($native->getExtension(), $lib->getExtension(), $messagePrefix.'getExtension()');
        self::assertSame($native->getExtensionName() ?: null, $lib->getExtensionName(), $messagePrefix . 'getExtensionName()');
        // self::assertSame($native->getFileName(), $lib->getFileName(), $messagePrefix.'getFileName()');
        self::assertSame($native->getModifiers(), $lib->getModifiers(), $messagePrefix . 'getModifiers()');
        self::assertSame($native->getName(), $lib->getName(), $messagePrefix . 'getName()');
        self::assertSame($native->getNamespaceName(), $lib->getNamespaceName(), $messagePrefix . 'getNamespaceName()');
        self::assertSame($native->getNumberOfParameters(), $lib->getNumberOfParameters(), $messagePrefix . 'getNumberOfParameters()');
        self::assertSame($native->getNumberOfRequiredParameters(), $lib->getNumberOfRequiredParameters(), $messagePrefix . 'getNumberOfRequiredParameters()');
        // self::assertSame($native->getPrototype(), $lib->getPrototype(), $messagePrefix.'getPrototype()');
        self::assertSame($native->getShortName(), $lib->getShortName(), $messagePrefix . 'getShortName()');
        // self::assertSame($native->getStartLine(), $lib->getStartLine(), $messagePrefix.'getStartLine()');
        // self::assertSame($native->getStaticVariables(), $lib->getStaticVariables(), $messagePrefix.'getStaticVariables()');
        // self::assertSame($native->hasPrototype(), $lib->hasPrototype(), $messagePrefix.'hasPrototype()');
        self::assertSame($native->inNamespace(), $lib->inNamespace(), $messagePrefix . 'inNamespace()');
        // self::assertSame($native->invoke(), $lib->invoke(), $messagePrefix.'invoke()');
        // self::assertSame($native->invokeArgs(), $lib->invokeArgs(), $messagePrefix.'invokeArgs()');
        self::assertSame($native->isAbstract(), $lib->isAbstract(), $messagePrefix . 'isAbstract()');
        self::assertSame($native->isClosure(), $lib->isClosure(), $messagePrefix . 'isClosure()');
        self::assertSame($native->isConstructor(), $lib->isConstructor(), $messagePrefix . 'isConstructor()');
        // self::assertSame($native->isDeprecated(), $lib->isDeprecated(), $messagePrefix.'isDeprecated()');
        self::assertSame($native->isDestructor(), $lib->isDestructor(), $messagePrefix . 'isDestructor()');
        self::assertSame($native->isFinal(), $lib->isFinal(), $messagePrefix . 'isFinal()');
        self::assertSame($native->isGenerator(), $lib->isGenerator(), $messagePrefix . 'isGenerator()');
        // self::assertSame($native->isInternal(), $lib->isInternal(), $messagePrefix.'isInternal()');
        self::assertSame($native->isPrivate(), $lib->isPrivate(), $messagePrefix . 'isPrivate()');
        self::assertSame($native->isProtected(), $lib->isProtected(), $messagePrefix . 'isProtected()');
        self::assertSame($native->isPublic(), $lib->isPublic(), $messagePrefix . 'isPublic()');
        self::assertSame($native->isStatic(), $lib->isStatic(), $messagePrefix . 'isStatic()');
        // self::assertSame($native->isUserDefined(), $lib->isUserDefined(), $messagePrefix.'isUserDefined()');
        self::assertSame($native->isVariadic(), $lib->isVariadic(), $messagePrefix . 'isVariadic()');
        self::assertSame($native->returnsReference(), $lib->returnsReference(), $messagePrefix . 'returnsReference()');
        self::assertSame(array_column($native->getParameters(), 'name'), array_column($lib->getParameters(), 'name'), $messagePrefix . 'getParameters().name');

        foreach ($native->getParameters() as $index => $nativeParameter) {
            self::assertTrue($lib->hasParameter($nativeParameter->name), $messagePrefix . "hasParameter({$nativeParameter->name}).");
            self::assertTrue($lib->hasParameter($nativeParameter->getPosition()), $messagePrefix . "hasParameter({$nativeParameter->getPosition()}).");
            $this->assertParameterEquals($nativeParameter, $lib->getParameters()[$index], $messagePrefix . "getParameter()[{$index}].");
        }
    }

    private function assertParameterEquals(\ReflectionParameter $native, ParameterReflection $lib, string $messagePrefix): void
    {
        self::assertSame($native->name, $lib->name, $messagePrefix . 'name');
        self::assertSame($native->canBePassedByValue(), $lib->canBePassedByValue(), $messagePrefix . 'canBePassedByValue()');
        // self::assertSame($native->getAttributes(), $lib->getAttributes(), $messagePrefix.'getAttributes()');
        // self::assertSame($native->getDeclaringClass(), $lib->getDeclaringClass(), $messagePrefix.'getDeclaringClass()');
        // self::assertSame($native->getDeclaringFunction(), $lib->getDeclaringFunction(), $messagePrefix.'getDeclaringFunction()');
        // self::assertSame($native->getDefaultValueConstantName(), $lib->getDefaultValueConstantName(), $messagePrefix.'getDefaultValueConstantName()');
        self::assertSame($native->getName(), $lib->getName(), $messagePrefix . 'getName()');
        self::assertSame($native->getPosition(), $lib->getPosition(), $messagePrefix . 'getPosition()');
        self::assertSame($native->isDefaultValueAvailable(), $lib->isDefaultValueAvailable(), $messagePrefix . 'isDefaultValueAvailable()');
        if ($native->isDefaultValueAvailable()) {
            self::assertEquals($native->getDefaultValue(), $lib->getDefaultValue(), $messagePrefix . 'getDefaultValue()');
        }
        // self::assertSame($native->isDefaultValueConstant(), $lib->isDefaultValueConstant(), $messagePrefix.'isDefaultValueConstant()');
        self::assertSame($native->isOptional(), $lib->isOptional(), $messagePrefix . 'isOptional()');
        self::assertSame($native->isPassedByReference(), $lib->isPassedByReference(), $messagePrefix . 'isPassedByReference()');
        self::assertSame($native->isPromoted(), $lib->isPromoted(), $messagePrefix . 'isPromoted()');
        self::assertSame($native->isVariadic(), $lib->isVariadic(), $messagePrefix . 'isVariadic()');
    }
}
