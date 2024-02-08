<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Mockery\Loader\RequireLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\ClassLocator\NativeReflectionLocator;
use Typhoon\Reflection\NameContext\AnonymousClassName;

#[CoversClass(AttributeReflection::class)]
#[CoversClass(ClassReflection::class)]
#[CoversClass(MethodReflection::class)]
#[CoversClass(ParameterReflection::class)]
#[CoversClass(PropertyReflection::class)]
final class ReflectorCompatibilityTest extends TestCase
{
    private const CLASSES = __DIR__ . '/ReflectorCompatibility/classes.php';
    private const READONLY_CLASSES = __DIR__ . '/ReflectorCompatibility/readonly_classes.php';

    public static function setUpBeforeClass(): void
    {
        \Mockery::setLoader(new RequireLoader(__DIR__ . '/../../var/mockery'));
    }

    /**
     * @return \Generator<string, ReflectionSession>
     */
    public static function reflectorSessions(): \Generator
    {
        yield 'default reflector' => TyphoonReflector::build()->startSession();
        yield 'native reflector' => TyphoonReflector::build(classLocators: [new NativeReflectionLocator()])->startSession();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return \Generator<string, array{ReflectionSession, string}>
     */
    public static function classes(): \Generator
    {
        /** @psalm-suppress UnresolvableInclude */
        require_once self::CLASSES;

        $anonymousNames = AnonymousClassName::findDeclared(file: self::CLASSES);

        foreach (self::reflectorSessions() as $sessionName => $session) {
            yield \Iterator::class . ' using ' . $sessionName => [$session, \Iterator::class];

            foreach (NameCollector::collect(self::CLASSES)->classes as $class) {
                yield $class . ' using ' . $sessionName => [$session, $class];
            }

            foreach ($anonymousNames as $anonymousName) {
                yield 'anonymous at line ' . $anonymousName->line . ' using ' . $sessionName => [$session, $anonymousName->name];
            }
        }
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return \Generator<string, array{ReflectionSession, string}>
     */
    public static function readonlyClasses(): \Generator
    {
        if (\PHP_VERSION_ID >= 80200) {
            /** @psalm-suppress UnresolvableInclude */
            require_once self::READONLY_CLASSES;
        }

        foreach (self::reflectorSessions() as $sessionName => $session) {
            foreach (NameCollector::collect(self::READONLY_CLASSES)->classes as $class) {
                yield $class . ' using ' . $sessionName => [$session, $class];
            }
        }
    }

    #[DataProvider('classes')]
    public function testItReflectsClassesCompatibly(ReflectionSession $session, string $class): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $native = new \ReflectionClass($class);

        $typhoon = $session->reflectClass($class);

        $this->assertClassEquals($native, $typhoon);
    }

    #[RequiresPhp('>=8.2')]
    #[DataProvider('readonlyClasses')]
    public function testItReflectsReadonlyClasses(ReflectionSession $session, string $class): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $native = new \ReflectionClass($class);

        $typhoon = $session->reflectClass($class);

        /** @psalm-suppress UnusedPsalmSuppress, MixedArgument, UndefinedMethod */
        self::assertSame($native->isReadOnly(), $typhoon->isReadOnly());
    }

    private function assertClassEquals(\ReflectionClass $native, ClassReflection $typhoon): void
    {
        self::assertSame($native->name, $typhoon->name, 'class.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), 'class.__toString()');
        self::assertAttributesEqual($native->getAttributes(), $typhoon->getAttributes(), 'class.getAttributes()');
        // TODO getConstant()
        self::assertSame($native->getConstants(), $typhoon->getConstants(), 'class.getConstants()');
        self::assertSame($native->getConstructor()?->name, $typhoon->getConstructor()?->name, 'class.getConstructor().name');
        self::assertSame($native->getDefaultProperties(), $typhoon->getDefaultProperties(), 'class.getDefaultProperties()');
        self::assertSame($native->getDocComment(), $typhoon->getDocComment(), 'class.getDocComment()');
        self::assertSame($native->getEndLine(), $typhoon->getEndLine(), 'class.getEndLine()');
        self::assertEquals($native->getExtension(), $typhoon->getExtension(), 'class.getExtension()');
        self::assertEquals($native->getExtensionName(), $typhoon->getExtensionName(), 'class.getExtensionName()');
        self::assertSame($native->getFileName(), $typhoon->getFileName(), 'class.getFileName()');
        self::assertSame($native->getInterfaceNames(), $typhoon->getInterfaceNames(), 'class.getInterfaceNames()');
        $this->assertSameNames($native->getInterfaces(), $typhoon->getInterfaces(), 'class.getInterfaces().name');
        // getMethods() see below
        // getMethod() see below
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), 'class.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), 'class.getName()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), 'class.getNamespaceName()');
        self::assertSame(($native->getParentClass() ?: null)?->name, ($typhoon->getParentClass() ?: null)?->name, 'class.getParentClass().name');
        // getProperties() see below
        // getProperty() see below
        // TODO getReflectionConstant()
        self::assertEquals($native->getReflectionConstants(), $typhoon->getReflectionConstants(), 'class.getReflectionConstants()');
        self::assertSame($native->getShortName(), $typhoon->getShortName(), 'class.getShortName()');
        self::assertSame($native->getStartLine(), $typhoon->getStartLine(), 'class.getStartLine()');
        self::assertSame($native->getStaticProperties(), $typhoon->getStaticProperties(), 'class.getStaticProperties()');
        // getStaticPropertyValue()
        self::assertSame($native->getTraitAliases(), $typhoon->getTraitAliases(), 'class.getTraitAliases()');
        self::assertSame($native->getTraitNames(), $typhoon->getTraitNames(), 'class.getTraitNames()');
        self::assertEquals($native->getTraits(), $typhoon->getTraits(), 'class.getTraits()');
        // TODO hasConstant()
        // hasMethod() see below
        // hasProperty() see below
        foreach ($this->getClasses($native) as $class) {
            $this->assertResultOrExceptionEqual(
                native: static fn(): bool => $native->implementsInterface($class),
                typhoon: static fn(): bool => $typhoon->implementsInterface($class),
                messagePrefix: "class.implementsInterface({$class})",
            );
        }
        self::assertSame($native->inNamespace(), $typhoon->inNamespace(), 'class.inNamespace()');
        self::assertSame($native->isAbstract(), $typhoon->isAbstract(), 'class.isAbstract()');
        self::assertSame($native->isAnonymous(), $typhoon->isAnonymous(), 'class.isAnonymous()');
        self::assertSame($native->isCloneable(), $typhoon->isCloneable(), 'class.isCloneable()');
        self::assertSame($native->isEnum(), $typhoon->isEnum(), 'class.isEnum()');
        self::assertSame($native->isFinal(), $typhoon->isFinal(), 'class.isFinal()');
        if ($this->canCreateMockObject($native)) {
            self::assertSame($native->isInstance($this->createMockObject($native)), $typhoon->isInstance($this->createMockObject($native)), 'class.isInstance()');
        }
        self::assertSame($native->isInstantiable(), $typhoon->isInstantiable(), 'class.isInstantiable()');
        self::assertSame($native->isInterface(), $typhoon->isInterface(), 'class.isInterface()');
        self::assertSame($native->isInternal(), $typhoon->isInternal(), 'class.isInternal()');
        self::assertSame($native->isIterable(), $typhoon->isIterable(), 'class.isIterable()');
        self::assertSame($native->isIterateable(), $typhoon->isIterateable(), 'class.isIterateable()');
        if (method_exists($native, 'isReadOnly')) {
            self::assertSame($native->isReadOnly(), $typhoon->isReadOnly(), 'class.isReadOnly()');
        }
        foreach ($this->getClasses($native) as $class) {
            $this->assertResultOrExceptionEqual(
                native: static fn(): bool => $native->isSubclassOf($class),
                typhoon: static fn(): bool => $typhoon->isSubclassOf($class),
                messagePrefix: "class.isSubclassOf({$class})",
            );
        }
        self::assertSame($native->isTrait(), $typhoon->isTrait(), 'class.isTrait()');
        self::assertSame($native->isUserDefined(), $typhoon->isUserDefined(), 'class.isUserDefined()');
        if ($native->isInstantiable()) {
            // self::assertEquals($native->newInstance(), $typhoon->newInstance(), 'class.newInstance()');
            // self::assertEquals($native->newInstanceArgs(), $typhoon->newInstanceArgs(), 'class.newInstanceArgs()');
            self::assertEquals($native->newInstanceWithoutConstructor(), $typhoon->newInstanceWithoutConstructor(), 'class.newInstanceWithoutConstructor()');
        }
        // TODO setStaticPropertyValue()

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

        foreach ($native->getMethods() as $nativeMethod) {
            self::assertTrue($typhoon->hasMethod($nativeMethod->name), "hasMethod({$nativeMethod->name})");
            $this->assertMethodEquals($nativeMethod, $typhoon->getMethod($nativeMethod->name), "getMethod({$nativeMethod->name})");
        }
    }

    private function assertPropertyEquals(\ReflectionProperty $native, PropertyReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->class, $typhoon->class, $messagePrefix . '.class');
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertAttributesEqual($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix . 'getAttributes()');
        self::assertSame($native->getDeclaringClass()->name, $typhoon->getDeclaringClass()->name, $messagePrefix . '.getDeclaringClass()');
        self::assertSame($native->getDefaultValue(), $typhoon->getDefaultValue(), $messagePrefix . '.getDefaultValue()');
        self::assertSame($native->getDocComment(), $typhoon->getDocComment(), $messagePrefix . '.getDocComment()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . '.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertEquals($native->getType(), $typhoon->getType(), $messagePrefix . '.getType()');
        // TODO getValue()
        self::assertSame($native->hasDefaultValue(), $typhoon->hasDefaultValue(), $messagePrefix . '.hasDefaultValue()');
        self::assertSame($native->hasType(), $typhoon->hasType(), $messagePrefix . '.hasType()');
        self::assertSame($native->isDefault(), $typhoon->isDefault(), $messagePrefix . '.isDefault()');
        // TODO isInitialized()
        self::assertSame($native->isPrivate(), $typhoon->isPrivate(), $messagePrefix . '.isPrivate()');
        self::assertSame($native->isPromoted(), $typhoon->isPromoted(), $messagePrefix . '.isPromoted()');
        self::assertSame($native->isProtected(), $typhoon->isProtected(), $messagePrefix . '.isProtected()');
        self::assertSame($native->isPublic(), $typhoon->isPublic(), $messagePrefix . '.isPublic()');
        self::assertSame($native->isReadOnly(), $typhoon->isReadOnly(), $messagePrefix . '.isReadOnly()');
        self::assertSame($native->isStatic(), $typhoon->isStatic(), $messagePrefix . '.isStatic()');
        $typhoon->setAccessible(true);
        // TODO setValue()
    }

    private function assertMethodEquals(\ReflectionMethod $native, MethodReflection $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->class, $typhoon->class, $messagePrefix . '.class');
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        // TODO: self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertAttributesEqual($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix . 'getAttributes()');
        if ($native->isStatic()) {
            $this->assertMethodClosureEquals($native->getClosure(), $typhoon->getClosure(), $messagePrefix . '.getClosure()');
        } elseif ($this->canCreateMockObject($native->getDeclaringClass())) {
            $object = $this->createMockObject($native->getDeclaringClass());
            $this->assertMethodClosureEquals($native->getClosure($object), $typhoon->getClosure($object), $messagePrefix . '.getClosure($object)');
        }
        self::assertSame($native->getClosureCalledClass(), $typhoon->getClosureCalledClass(), $messagePrefix . '.getClosureCalledClass()');
        self::assertSame($native->getClosureScopeClass(), $typhoon->getClosureScopeClass(), $messagePrefix . '.getClosureScopeClass()');
        self::assertSame($native->getClosureThis(), $typhoon->getClosureThis(), $messagePrefix . '.getClosureThis()');
        self::assertSame($native->getClosureUsedVariables(), $typhoon->getClosureUsedVariables(), $messagePrefix . '.getClosureUsedVariables()');
        self::assertSame($native->getDeclaringClass()->name, $typhoon->getDeclaringClass()->name, $messagePrefix . '.getDeclaringClass()');
        self::assertSame($native->getDocComment(), $typhoon->getDocComment(), $messagePrefix . '.getDocComment()');
        self::assertSame($native->getEndLine(), $typhoon->getEndLine(), $messagePrefix . '.getEndLine()');
        self::assertEquals($native->getExtension(), $typhoon->getExtension(), $messagePrefix . '.getExtension()');
        self::assertSame($native->getExtensionName(), $typhoon->getExtensionName(), $messagePrefix . '.getExtensionName()');
        self::assertSame($native->getFileName(), $typhoon->getFileName(), $messagePrefix . '.getFileName()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . '.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), $messagePrefix . '.getNamespaceName()');
        self::assertSame($native->getNumberOfParameters(), $typhoon->getNumberOfParameters(), $messagePrefix . '.getNumberOfParameters()');
        self::assertSame($native->getNumberOfRequiredParameters(), $typhoon->getNumberOfRequiredParameters(), $messagePrefix . '.getNumberOfRequiredParameters()');
        $this->assertParametersEqual($native->getParameters(), $typhoon->getParameters(), $messagePrefix . '.getParameters()');
        $this->assertResultOrExceptionEqual(
            native: static fn(): string => $native->getPrototype()->class,
            typhoon: static fn(): string => $typhoon->getPrototype()->class,
            messagePrefix: $messagePrefix . '.getPrototype().class',
        );
        $this->assertResultOrExceptionEqual(
            native: static fn(): string => $native->getPrototype()->name,
            typhoon: static fn(): string => $typhoon->getPrototype()->name,
            messagePrefix: $messagePrefix . '.getPrototype().name',
        );
        self::assertSame($native->getShortName(), $typhoon->getShortName(), $messagePrefix . '.getShortName()');
        self::assertSame($native->getStartLine(), $typhoon->getStartLine(), $messagePrefix . '.getStartLine()');
        self::assertSame($native->getStaticVariables(), $typhoon->getStaticVariables(), $messagePrefix . '.getStaticVariables()');
        self::assertEquals($native->getTentativeReturnType(), $typhoon->getTentativeReturnType(), $messagePrefix . '.getTentativeReturnType()');
        if (method_exists($native, 'hasPrototype')) {
            self::assertSame($native->hasPrototype(), $typhoon->hasPrototype(), $messagePrefix . '.hasPrototype()');
        }
        self::assertSame($native->hasReturnType(), $typhoon->hasReturnType(), $messagePrefix . '.hasReturnType()');
        self::assertSame($native->hasTentativeReturnType(), $typhoon->hasTentativeReturnType(), $messagePrefix . '.hasTentativeReturnType()');
        self::assertSame($native->inNamespace(), $typhoon->inNamespace(), $messagePrefix . '.inNamespace()');
        // TODO invoke()
        // TODO invokeArgs()
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
    }

    /**
     * @param array<\ReflectionParameter> $native
     * @param array<\ReflectionParameter> $typhoon
     */
    private function assertParametersEqual(array $native, array $typhoon, string $messagePrefix): void
    {
        $this->assertSameNames($native, $typhoon, $messagePrefix . '.name');

        foreach ($native as $position => $parameter) {
            $this->assertParameterEquals($parameter, $typhoon[$position], $messagePrefix . ".getParameter()[{$position} ({$parameter->name})]");
        }
    }

    private function assertParameterEquals(\ReflectionParameter $native, \ReflectionParameter $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertSame($native->allowsNull(), $typhoon->allowsNull(), $messagePrefix . '.allowsNull()');
        self::assertSame($native->canBePassedByValue(), $typhoon->canBePassedByValue(), $messagePrefix . '.canBePassedByValue()');
        self::assertAttributesEqual($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix . 'getAttributes()');
        self::assertSame($native->getClass()?->name, $typhoon->getClass()?->name, $messagePrefix . '.getClass().name');
        self::assertSame($native->getDeclaringClass()?->name, $typhoon->getDeclaringClass()?->name, $messagePrefix . '.getDeclaringClass().name');
        self::assertSame($native->getDeclaringFunction()->name, $typhoon->getDeclaringFunction()->name, $messagePrefix . '.getDeclaringFunction().name');
        if ($native->isDefaultValueAvailable()) {
            self::assertSame($native->getDefaultValueConstantName(), $typhoon->getDefaultValueConstantName(), $messagePrefix . '.getDefaultValueConstantName()');
        }
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertSame($native->getPosition(), $typhoon->getPosition(), $messagePrefix . '.getPosition()');
        self::assertEquals($native->getType(), $typhoon->getType(), $messagePrefix . '.getType()');
        self::assertSame($native->hasType(), $typhoon->hasType(), $messagePrefix . '.hasType()');
        self::assertSame($native->isArray(), $typhoon->isArray(), $messagePrefix . '.isArray()');
        self::assertSame($native->isCallable(), $typhoon->isCallable(), $messagePrefix . '.isCallable()');
        self::assertSame($native->isDefaultValueAvailable(), $typhoon->isDefaultValueAvailable(), $messagePrefix . '.isDefaultValueAvailable()');
        if ($native->isDefaultValueAvailable()) {
            self::assertEquals($native->getDefaultValue(), $typhoon->getDefaultValue(), $messagePrefix . '.getDefaultValue()');
            self::assertSame($native->isDefaultValueConstant(), $typhoon->isDefaultValueConstant(), $messagePrefix . '.isDefaultValueConstant()');
        }
        self::assertSame($native->isOptional(), $typhoon->isOptional(), $messagePrefix . '.isOptional()');
        self::assertSame($native->isPassedByReference(), $typhoon->isPassedByReference(), $messagePrefix . '.isPassedByReference()');
        self::assertSame($native->isPromoted(), $typhoon->isPromoted(), $messagePrefix . '.isPromoted()');
        self::assertSame($native->isVariadic(), $typhoon->isVariadic(), $messagePrefix . '.isVariadic()');
    }

    /**
     * @param array<\ReflectionAttribute> $native
     * @param array<\ReflectionAttribute> $typhoon
     */
    private function assertAttributesEqual(array $native, array $typhoon, string $messagePrefix): void
    {
        self::assertCount(\count($native), $typhoon, $messagePrefix . '.count');

        foreach ($native as $index => $nativeAttr) {
            self::assertArrayHasKey($index, $typhoon);
            $typhoonAttr = $typhoon[$index];
            self::assertSame($nativeAttr->__toString(), $typhoonAttr->__toString(), $messagePrefix . '.__toString()');
            self::assertEquals($nativeAttr->getArguments(), $typhoonAttr->getArguments(), $messagePrefix . '.getArguments()');
            self::assertSame($nativeAttr->getName(), $typhoonAttr->getName(), $messagePrefix . '.getName()');
            self::assertSame($nativeAttr->getTarget(), $typhoonAttr->getTarget(), $messagePrefix . '.getTarget()');
            self::assertSame($nativeAttr->isRepeated(), $typhoonAttr->isRepeated(), $messagePrefix . '.isRepeated()');
            self::assertEquals($nativeAttr->newInstance(), $typhoonAttr->newInstance(), $messagePrefix . '.newInstance()');
        }
    }

    private function assertMethodClosureEquals(\Closure $native, \Closure $typhoon, string $messagePrefix): void
    {
        $nativeReflection = new \ReflectionFunction($native);
        $typhoonReflection = new \ReflectionFunction($typhoon);

        self::assertSame($nativeReflection->isStatic(), $typhoonReflection->isStatic(), $messagePrefix . '.isStatic()');
        self::assertSame($nativeReflection->getClosureCalledClass()?->name, $typhoonReflection->getClosureCalledClass()?->name, $messagePrefix . '.getClosureCalledClass()');
        self::assertSame($nativeReflection->getClosureScopeClass()?->name, $typhoonReflection->getClosureScopeClass()?->name, $messagePrefix . '.getClosureCalledClass()');
        self::assertSame($nativeReflection->getClosureThis(), $typhoonReflection->getClosureThis(), $messagePrefix . '.getClosureThis()');
        $this->assertParametersEqual($nativeReflection->getParameters(), $typhoonReflection->getParameters(), $messagePrefix . '.getParameters()');
    }

    /**
     * @param array<\ReflectionClass|\ReflectionProperty|\ReflectionMethod|\ReflectionParameter> $nativeReflections
     * @param array<\ReflectionClass|\ReflectionProperty|\ReflectionMethod|\ReflectionParameter> $typhoonReflections
     */
    private function assertSameNames(array $nativeReflections, array $typhoonReflections, string $message): void
    {
        self::assertSame(array_column($nativeReflections, 'name'), array_column($typhoonReflections, 'name'), $message);
    }

    private function canCreateMockObject(\ReflectionClass $class): bool
    {
        if ($class->isTrait()) {
            return false;
        }

        if ($class->isEnum()) {
            /** @psalm-suppress MixedMethodCall */
            return $class->name::cases() !== [];
        }

        return true;
    }

    /**
     * @return \Generator<int, class-string>
     * @psalm-suppress MoreSpecificReturnType
     */
    private function getClasses(\ReflectionClass $class): \Generator
    {
        yield '';
        yield 'HELLO!';
        yield $class->name;
        yield from $class->getInterfaceNames();
        $parent = $class->getParentClass();

        while ($parent !== false) {
            yield $parent->name;
            $parent = $parent->getParentClass();
        }

        yield \Iterator::class;
        yield \ArrayAccess::class;
        yield \Throwable::class;
        yield \UnitEnum::class;
        yield Variance::class;
        yield \FilterIterator::class;
        yield \stdClass::class;

        // TODO add trait
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $class
     * @return T
     */
    private function createMockObject(\ReflectionClass $class): object
    {
        if ($class->isAbstract() || $class->isInterface()) {
            /** @var T */
            return \Mockery::mock($class->name);
        }

        if ($class->isEnum()) {
            /**
             * @var list<T>
             * @psalm-suppress MixedMethodCall
             */
            $cases = $class->name::cases();

            if ($cases === []) {
                throw new \LogicException(sprintf('Enum %s has no cases.', $class->name));
            }

            return $cases[0];
        }

        return $class->newInstanceWithoutConstructor();
    }

    private function assertResultOrExceptionEqual(\Closure $native, \Closure $typhoon, string $messagePrefix): void
    {
        $nativeException = null;
        $nativeResult = null;
        $typhoonException = null;
        $typhoonResult = null;

        try {
            /** @psalm-suppress MixedAssignment */
            $nativeResult = $native();
        } catch (\Throwable $nativeException) {
        }

        try {
            /** @psalm-suppress MixedAssignment */
            $typhoonResult =  $typhoon();
        } catch (\Throwable $typhoonException) {
        }

        self::assertSame($nativeResult, $typhoonResult, $messagePrefix);

        if ($nativeException !== null) {
            $messagePrefix .= '.exception';
            self::assertInstanceOf($nativeException::class, $typhoonException, $messagePrefix . '.class');
            self::assertSame($nativeException->getMessage(), $typhoonException->getMessage(), $messagePrefix . '.getMessage()');
            self::assertEquals($nativeException->getPrevious(), $typhoonException->getPrevious(), $messagePrefix . '.getPrevious()');
            self::assertSame($nativeException->getCode(), $typhoonException->getCode(), $messagePrefix . '.getCode()');
        }
    }
}
