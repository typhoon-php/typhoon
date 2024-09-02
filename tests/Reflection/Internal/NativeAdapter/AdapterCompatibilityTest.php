<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Attributes\Attr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Traits\Trait1;
use Typhoon\DeclarationId\Id;
use Typhoon\PhpStormReflectionStubs\PhpStormStubsLocator;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Variance;

#[CoversClass(AttributeAdapter::class)]
#[CoversClass(FunctionAdapter::class)]
#[CoversClass(ParameterAdapter::class)]
#[CoversClass(ClassAdapter::class)]
#[CoversClass(EnumAdapter::class)]
#[CoversClass(ClassConstantAdapter::class)]
#[CoversClass(EnumUnitCaseAdapter::class)]
#[CoversClass(EnumBackedCaseAdapter::class)]
#[CoversClass(PropertyAdapter::class)]
#[CoversClass(MethodAdapter::class)]
#[CoversClass(ToNativeTypeConverter::class)]
#[CoversClass(NamedTypeAdapter::class)]
#[CoversClass(UnionTypeAdapter::class)]
#[CoversClass(IntersectionTypeAdapter::class)]
final class AdapterCompatibilityTest extends TestCase
{
    private static TyphoonReflector $defaultReflector;

    private static TyphoonReflector $noStubsLocator;

    public static function setUpBeforeClass(): void
    {
        self::$defaultReflector = TyphoonReflector::build();
        self::$noStubsLocator = TyphoonReflector::build(locators: array_filter(
            TyphoonReflector::defaultLocators(),
            static fn(object $locator): bool => !$locator instanceof PhpStormStubsLocator,
        ));
    }

    /**
     * @param callable-string $name
     */
    #[DataProviderExternal(FunctionFixtures::class, 'get')]
    public function testFunctions(string $name): void
    {
        $native = new \ReflectionFunction($name);

        $typhoon = self::$defaultReflector->reflectFunction($name)->toNativeReflection();

        self::assertFunctionEquals($native, $typhoon);
    }

    /**
     * @param callable-string $name
     */
    #[DataProviderExternal(FunctionFixtures::class, 'internal')]
    public function testInternalFunctionsViaNativeReflector(string $name): void
    {
        $native = new \ReflectionFunction($name);

        $typhoon = self::$noStubsLocator->reflectFunction($name)->toNativeReflection();

        self::assertFunctionEquals($native, $typhoon);
    }

    /**
     * @param class-string $name
     */
    #[DataProviderExternal(ClassFixtures::class, 'get')]
    public function testClasses(string $name): void
    {
        $native = new \ReflectionClass($name);
        $native = $native->isEnum() ? new \ReflectionEnum($name) : $native;

        $typhoon = self::$defaultReflector->reflectClass($name)->toNativeReflection();

        self::assertClassEquals($native, $typhoon);
    }

    /**
     * @param class-string $name
     */
    #[DataProviderExternal(ClassFixtures::class, 'internal')]
    public function testInternalClassesViaNativeReflector(string $name): void
    {
        $native = new \ReflectionClass($name);
        $native = $native->isEnum() ? new \ReflectionEnum($name) : $native;

        $typhoon = self::$noStubsLocator->reflectClass($name)->toNativeReflection();

        self::assertClassEquals($native, $typhoon);
    }

    private static function assertFunctionEquals(\ReflectionFunction $native, \ReflectionFunction $typhoon, string $messagePrefix = 'function'): void
    {
        self::assertTrue(isset($typhoon->name), "isset({$messagePrefix}.name)");
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertGetAttributes($native, $typhoon, $messagePrefix);
        self::assertMethodClosureEquals($native->getClosure(), $typhoon->getClosure(), $messagePrefix . '.getClosure()');
        self::assertSame($native->getClosureCalledClass(), $typhoon->getClosureCalledClass(), $messagePrefix . '.getClosureCalledClass()');
        self::assertSame($native->getClosureScopeClass(), $typhoon->getClosureScopeClass(), $messagePrefix . '.getClosureScopeClass()');
        self::assertSame($native->getClosureThis(), $typhoon->getClosureThis(), $messagePrefix . '.getClosureThis()');
        self::assertSame($native->getClosureUsedVariables(), $typhoon->getClosureUsedVariables(), $messagePrefix . '.getClosureUsedVariables()');
        self::assertSame($native->getDocComment(), $typhoon->getDocComment(), $messagePrefix . '.getDocComment()');
        self::assertSame($native->getEndLine(), $typhoon->getEndLine(), $messagePrefix . '.getEndLine()');
        self::assertEquals($native->getExtension(), $typhoon->getExtension(), $messagePrefix . '.getExtension()');
        self::assertSame($native->getExtensionName(), $typhoon->getExtensionName(), $messagePrefix . '.getExtensionName()');
        self::assertSame($native->getFileName(), $typhoon->getFileName(), $messagePrefix . '.getFileName()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), $messagePrefix . '.getNamespaceName()');
        self::assertSame($native->getNumberOfParameters(), $typhoon->getNumberOfParameters(), $messagePrefix . '.getNumberOfParameters()');
        self::assertSame($native->getNumberOfRequiredParameters(), $typhoon->getNumberOfRequiredParameters(), $messagePrefix . '.getNumberOfRequiredParameters()');
        self::assertParametersEqual($native->getParameters(), $typhoon->getParameters(), $messagePrefix . '.getParameters()');
        self::assertSame($native->getShortName(), $typhoon->getShortName(), $messagePrefix . '.getShortName()');
        self::assertSame($native->getStartLine(), $typhoon->getStartLine(), $messagePrefix . '.getStartLine()');
        self::assertSame($native->getStaticVariables(), $typhoon->getStaticVariables(), $messagePrefix . '.getStaticVariables()');
        self::assertTypeEquals($native->getReturnType(), $typhoon->getReturnType(), $messagePrefix . '.getReturnType()');
        self::assertTypeEquals($native->getTentativeReturnType(), $typhoon->getTentativeReturnType(), $messagePrefix . '.getTentativeReturnType()');
        self::assertSame($native->hasReturnType(), $typhoon->hasReturnType(), $messagePrefix . '.hasReturnType()');
        self::assertSame($native->hasTentativeReturnType(), $typhoon->hasTentativeReturnType(), $messagePrefix . '.hasTentativeReturnType()');
        self::assertSame($native->inNamespace(), $typhoon->inNamespace(), $messagePrefix . '.inNamespace()');
        // TODO invoke()
        // TODO invokeArgs()
        if (method_exists(\ReflectionFunction::class, 'isAnonymous')) {
            /** @psalm-suppress MixedArgument, UnusedPsalmSuppress */
            self::assertSame($native->isAnonymous(), $typhoon->isAnonymous(), $messagePrefix . '.isAnonymous()');
        }
        self::assertSame($native->isClosure(), $typhoon->isClosure(), $messagePrefix . '.isClosure()');
        self::assertSame($native->isDeprecated(), $typhoon->isDeprecated(), $messagePrefix . '.isDeprecated()');
        self::assertSame($native->isDisabled(), $typhoon->isDisabled(), $messagePrefix . '.isDisabled()');
        self::assertSame($native->isGenerator(), $typhoon->isGenerator(), $messagePrefix . '.isGenerator()');
        self::assertSame($native->isInternal(), $typhoon->isInternal(), $messagePrefix . '.isInternal()');
        self::assertSame($native->isStatic(), $typhoon->isStatic(), $messagePrefix . '.isStatic()');
        self::assertSame($native->isUserDefined(), $typhoon->isUserDefined(), $messagePrefix . '.isUserDefined()');
        self::assertSame($native->isVariadic(), $typhoon->isVariadic(), $messagePrefix . '.isVariadic()');
        self::assertSame($native->returnsReference(), $typhoon->returnsReference(), $messagePrefix . '.returnsReference()');
    }

    private static function assertClassEquals(\ReflectionClass $native, \ReflectionClass $typhoon): void
    {
        self::assertTrue(isset($typhoon->name), 'isset(class.name)');
        self::assertSame($native->name, $typhoon->name, 'class.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), 'class.__toString()');
        self::assertGetAttributes($native, $typhoon, 'c');
        self::assertSame($native->getConstructor()?->name, $typhoon->getConstructor()?->name, 'class.getConstructor().name');
        self::assertSame($native->getDefaultProperties(), $typhoon->getDefaultProperties(), 'class.getDefaultProperties()');
        self::assertSame($native->getDocComment(), $typhoon->getDocComment(), 'class.getDocComment()');
        self::assertSame($native->getEndLine(), $typhoon->getEndLine(), 'class.getEndLine()');
        self::assertEquals($native->getExtension(), $typhoon->getExtension(), 'class.getExtension()');
        self::assertEquals($native->getExtensionName(), $typhoon->getExtensionName(), 'class.getExtensionName()');
        self::assertSame($native->getFileName(), $typhoon->getFileName(), 'class.getFileName()');
        self::assertInterfaceNamesEqualNoOrder($native->getInterfaceNames(), $typhoon->getInterfaceNames(), 'class.getInterfaceNames()');
        self::assertReflectionsEqualNoOrder($native->getInterfaces(), $typhoon->getInterfaces(), 'class.getInterfaces()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), 'class.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), 'class.getName()');
        self::assertSame($native->getNamespaceName(), $typhoon->getNamespaceName(), 'class.getNamespaceName()');
        self::assertSame(($native->getParentClass() ?: null)?->name, ($typhoon->getParentClass() ?: null)?->name, 'class.getParentClass().name');
        self::assertSame($native->getShortName(), $typhoon->getShortName(), 'class.getShortName()');
        self::assertSame($native->getStartLine(), $typhoon->getStartLine(), 'class.getStartLine()');
        self::assertSame($native->getStaticProperties(), $typhoon->getStaticProperties(), 'class.getStaticProperties()');
        // TODO getStaticPropertyValue()
        self::assertSame($native->getTraitAliases(), $typhoon->getTraitAliases(), 'class.getTraitAliases()');
        self::assertSame($native->getTraitNames(), $typhoon->getTraitNames(), 'class.getTraitNames()');
        self::assertReflectionsEqual($native->getTraits(), $typhoon->getTraits(), 'class.getTraits()');
        foreach (self::getClasses($native) as $class) {
            self::assertResultOrExceptionEqual(
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
        foreach (self::getObjects($native) as $object) {
            self::assertSame($native->isInstance($object), $typhoon->isInstance($object), \sprintf('class.isInstance(%s)', $object::class));
        }
        self::assertSame($native->isInstantiable(), $typhoon->isInstantiable(), 'class.isInstantiable()');
        self::assertSame($native->isInterface(), $typhoon->isInterface(), 'class.isInterface()');
        self::assertSame($native->isInternal(), $typhoon->isInternal(), 'class.isInternal()');
        self::assertSame($native->isIterable(), $typhoon->isIterable(), 'class.isIterable()');
        self::assertSame($native->isIterateable(), $typhoon->isIterateable(), 'class.isIterateable()');
        if (method_exists(\ReflectionClass::class, 'isReadOnly')) {
            /** @psalm-suppress MixedArgument, UnusedPsalmSuppress */
            self::assertSame($native->isReadOnly(), $typhoon->isReadOnly(), 'class.isReadOnly()');
        }
        foreach (self::getClasses($native) as $class) {
            self::assertResultOrExceptionEqual(
                native: static fn(): bool => $native->isSubclassOf($class),
                typhoon: static fn(): bool => $typhoon->isSubclassOf($class),
                messagePrefix: "class.isSubclassOf({$class})",
            );
        }
        self::assertSame($native->isTrait(), $typhoon->isTrait(), 'class.isTrait()');
        self::assertSame($native->isUserDefined(), $typhoon->isUserDefined(), 'class.isUserDefined()');
        // newInstance()
        // newInstanceArgs()
        // newInstanceWithoutConstructor()
        // TODO setStaticPropertyValue()

        if ($native instanceof \ReflectionEnum) {
            self::assertInstanceOf(\ReflectionEnum::class, $typhoon, 'class::class');
            self::assertReflectionsEqualNoOrder($native->getCases(), $typhoon->getCases(), 'class.getCases()');
            self::assertTypeEquals($native->getBackingType(), $typhoon->getBackingType(), 'class.getBackingType()');

            foreach ($native->getCases() as $nativeCase) {
                self::assertTrue($typhoon->hasCase($nativeCase->name), "class.hasCase({$nativeCase->name})");
                $typhoonCase = $typhoon->getCase($nativeCase->name);
                self::assertConstantEquals($nativeCase, $typhoonCase, "class.getCase({$nativeCase->name})");
            }
        }

        // CONSTANTS

        self::assertSame($native->getConstants(), $typhoon->getConstants(), 'class.getConstants().name');

        self::assertReflectionsEqualNoOrder($native->getReflectionConstants(), $typhoon->getReflectionConstants(), 'class.getReflectionConstants()');

        foreach ($native->getReflectionConstants() as $nativeConstant) {
            self::assertTrue($typhoon->hasConstant($nativeConstant->name), "class.hasConstant({$nativeConstant->name})");
            self::assertSame($native->getConstant($nativeConstant->name), $typhoon->getConstant($nativeConstant->name), "class.getConstant({$nativeConstant->name})");
            $typhoonConstant = $typhoon->getReflectionConstant($nativeConstant->name);
            self::assertNotFalse($typhoonConstant);
            self::assertConstantEquals($nativeConstant, $typhoonConstant, "class.getReflectionConstant({$nativeConstant->name})");
        }

        self::assertSame($native->getConstants(0), $typhoon->getConstants(0), 'class.getConstants(0).name');
        self::assertSame($native->getConstants(\ReflectionClassConstant::IS_PUBLIC), $typhoon->getConstants(\ReflectionClassConstant::IS_PUBLIC), 'class.getConstants(IS_PUBLIC).name');
        self::assertSame($native->getConstants(\ReflectionClassConstant::IS_PROTECTED), $typhoon->getConstants(\ReflectionClassConstant::IS_PROTECTED), 'class.getConstants(IS_PROTECTED).name');
        self::assertSame($native->getConstants(\ReflectionClassConstant::IS_PRIVATE), $typhoon->getConstants(\ReflectionClassConstant::IS_PRIVATE), 'class.getConstants(IS_PRIVATE).name');
        self::assertSame($native->getConstants(\ReflectionClassConstant::IS_FINAL), $typhoon->getConstants(\ReflectionClassConstant::IS_FINAL), 'class.getConstants(IS_FINAL).name');

        self::assertReflectionsEqualNoOrder($native->getReflectionConstants(0), $typhoon->getReflectionConstants(0), 'class.getReflectionConstants(0)');
        self::assertReflectionsEqualNoOrder($native->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC), $typhoon->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC), 'class.getReflectionConstants(IS_PUBLIC)');
        self::assertReflectionsEqualNoOrder($native->getReflectionConstants(\ReflectionClassConstant::IS_PROTECTED), $typhoon->getReflectionConstants(\ReflectionClassConstant::IS_PROTECTED), 'class.getReflectionConstants(IS_PROTECTED)');
        self::assertReflectionsEqualNoOrder($native->getReflectionConstants(\ReflectionClassConstant::IS_PRIVATE), $typhoon->getReflectionConstants(\ReflectionClassConstant::IS_PRIVATE), 'class.getReflectionConstants(IS_PRIVATE)');
        self::assertReflectionsEqualNoOrder($native->getReflectionConstants(\ReflectionClassConstant::IS_FINAL), $typhoon->getReflectionConstants(\ReflectionClassConstant::IS_FINAL), 'class.getReflectionConstants(IS_FINAL)');

        // PROPERTIES

        self::assertReflectionsEqualNoOrder($native->getProperties(), $typhoon->getProperties(), 'class.getProperties()');

        foreach ($native->getProperties() as $nativeProperty) {
            self::assertTrue($typhoon->hasProperty($nativeProperty->name), "class.hasProperty({$nativeProperty->name})");
            self::assertPropertyEquals($nativeProperty, $typhoon->getProperty($nativeProperty->name), "class.getProperty({$nativeProperty->name})");
        }

        self::assertReflectionsEqualNoOrder($native->getProperties(0), $typhoon->getProperties(0), 'class.getProperties(0)');
        self::assertReflectionsEqualNoOrder($native->getProperties(\ReflectionProperty::IS_PUBLIC), $typhoon->getProperties(\ReflectionProperty::IS_PUBLIC), 'class.getProperties(IS_PUBLIC)');
        self::assertReflectionsEqualNoOrder($native->getProperties(\ReflectionProperty::IS_PROTECTED), $typhoon->getProperties(\ReflectionProperty::IS_PROTECTED), 'class.getProperties(IS_PROTECTED)');
        self::assertReflectionsEqualNoOrder($native->getProperties(\ReflectionProperty::IS_PRIVATE), $typhoon->getProperties(\ReflectionProperty::IS_PRIVATE), 'class.getProperties(IS_PRIVATE)');
        self::assertReflectionsEqualNoOrder($native->getProperties(\ReflectionProperty::IS_STATIC), $typhoon->getProperties(\ReflectionProperty::IS_STATIC), 'class.getProperties(IS_STATIC)');
        self::assertReflectionsEqualNoOrder($native->getProperties(\ReflectionProperty::IS_READONLY), $typhoon->getProperties(\ReflectionProperty::IS_READONLY), 'class.getProperties(IS_READONLY)');

        // METHODS

        self::assertReflectionsEqualNoOrder($native->getMethods(), $typhoon->getMethods(), 'class.getMethods()');

        foreach ($native->getMethods() as $nativeMethod) {
            self::assertTrue($typhoon->hasMethod($nativeMethod->name), "hasMethod({$nativeMethod->name})");
            self::assertMethodEquals($nativeMethod, $typhoon->getMethod($nativeMethod->name), "getMethod({$nativeMethod->name})");
        }

        self::assertReflectionsEqualNoOrder($native->getMethods(0), $typhoon->getMethods(0), 'class.getMethods(0)');
        self::assertReflectionsEqualNoOrder($native->getMethods(\ReflectionMethod::IS_FINAL), $typhoon->getMethods(\ReflectionMethod::IS_FINAL), 'class.getMethods(IS_FINAL)');
        self::assertReflectionsEqualNoOrder($native->getMethods(\ReflectionMethod::IS_ABSTRACT), $typhoon->getMethods(\ReflectionMethod::IS_ABSTRACT), 'class.getMethods(IS_ABSTRACT)');
        self::assertReflectionsEqualNoOrder($native->getMethods(\ReflectionMethod::IS_PUBLIC), $typhoon->getMethods(\ReflectionMethod::IS_PUBLIC), 'class.getMethods(IS_PUBLIC)');
        self::assertReflectionsEqualNoOrder($native->getMethods(\ReflectionMethod::IS_PROTECTED), $typhoon->getMethods(\ReflectionMethod::IS_PROTECTED), 'class.getMethods(IS_PROTECTED)');
        self::assertReflectionsEqualNoOrder($native->getMethods(\ReflectionMethod::IS_PRIVATE), $typhoon->getMethods(\ReflectionMethod::IS_PRIVATE), 'class.getMethods(IS_PRIVATE)');
        self::assertReflectionsEqualNoOrder($native->getMethods(\ReflectionMethod::IS_STATIC), $typhoon->getMethods(\ReflectionMethod::IS_STATIC), 'class.getMethods(IS_STATIC)');
    }

    private static function assertConstantEquals(\ReflectionClassConstant $native, \ReflectionClassConstant $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->class, $typhoon->class, $messagePrefix . '.class');
        self::assertTrue(isset($typhoon->name), "isset({$messagePrefix}.name)");
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertGetAttributes($native, $typhoon, $messagePrefix);
        self::assertSame($native->getDeclaringClass()->name, $typhoon->getDeclaringClass()->name, $messagePrefix . '.getDeclaringClass()');
        self::assertSame($native->getDocComment(), $typhoon->getDocComment(), $messagePrefix . '.getDocComment()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . '.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        if (method_exists(\ReflectionClassConstant::class, 'getType')) {
            $nativeType = $native->getType();
            $typhoonType = $typhoon->getType();
            \assert($nativeType === null || $nativeType instanceof \ReflectionType);
            \assert($typhoonType === null || $typhoonType instanceof \ReflectionType);
            self::assertTypeEquals($nativeType, $typhoonType, $messagePrefix . '.getType()');
        }
        self::assertEquals($native->getValue(), $typhoon->getValue(), $messagePrefix . '.getValue()');
        if (method_exists(\ReflectionClassConstant::class, 'hasType')) {
            self::assertEquals($native->hasType(), $typhoon->hasType(), $messagePrefix . '.hasType()');
        }
        self::assertSame($native->isEnumCase(), $typhoon->isEnumCase(), $messagePrefix . '.isEnumCase()');
        self::assertSame($native->isFinal(), $typhoon->isFinal(), $messagePrefix . '.isFinal()');
        self::assertSame($native->isPrivate(), $typhoon->isPrivate(), $messagePrefix . '.isPrivate()');
        self::assertSame($native->isProtected(), $typhoon->isProtected(), $messagePrefix . '.isProtected()');
        self::assertSame($native->isPublic(), $typhoon->isPublic(), $messagePrefix . '.isPublic()');

        if ($native instanceof \ReflectionEnumUnitCase) {
            self::assertInstanceOf(\ReflectionEnumUnitCase::class, $typhoon, $messagePrefix . '::class');
            self::assertReflectionsEqualNoOrder([$native->getEnum()], [$typhoon->getEnum()], $messagePrefix . '.getEnum()');

            if ($native instanceof \ReflectionEnumBackedCase) {
                self::assertInstanceOf(\ReflectionEnumBackedCase::class, $typhoon, $messagePrefix . '::class');
                self::assertSame($native->getBackingValue(), $typhoon->getBackingValue(), $messagePrefix . '.getBackingValue()');
            }
        }
    }

    private static function assertPropertyEquals(\ReflectionProperty $native, \ReflectionProperty $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->class, $typhoon->class, $messagePrefix . '.class');
        self::assertTrue(isset($typhoon->name), "isset({$messagePrefix}.name)");
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertGetAttributes($native, $typhoon, $messagePrefix);
        self::assertSame($native->getDeclaringClass()->name, $typhoon->getDeclaringClass()->name, $messagePrefix . '.getDeclaringClass()');
        self::assertSame($native->getDefaultValue(), $typhoon->getDefaultValue(), $messagePrefix . '.getDefaultValue()');
        self::assertSame($native->getDocComment(), $typhoon->getDocComment(), $messagePrefix . '.getDocComment()');
        self::assertSame($native->getModifiers(), $typhoon->getModifiers(), $messagePrefix . '.getModifiers()');
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertTypeEquals($native->getType(), $typhoon->getType(), $messagePrefix . '.getType()');
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
        // TODO setValue()
    }

    private static function assertMethodEquals(\ReflectionMethod $native, \ReflectionMethod $typhoon, string $messagePrefix): void
    {
        self::assertSame($native->class, $typhoon->class, $messagePrefix . '.class');
        self::assertTrue(isset($typhoon->name), "isset({$messagePrefix}.name)");
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertGetAttributes($native, $typhoon, $messagePrefix);
        // TODO getClosure()
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
        self::assertParametersEqual($native->getParameters(), $typhoon->getParameters(), $messagePrefix . '.getParameters()');
        self::assertResultOrExceptionEqual(
            native: static fn(): string => $native->getPrototype()->class,
            typhoon: static fn(): string => $typhoon->getPrototype()->class,
            messagePrefix: $messagePrefix . '.getPrototype().class',
        );
        self::assertResultOrExceptionEqual(
            native: static fn(): string => $native->getPrototype()->name,
            typhoon: static fn(): string => $typhoon->getPrototype()->name,
            messagePrefix: $messagePrefix . '.getPrototype().name',
        );
        self::assertSame($native->getShortName(), $typhoon->getShortName(), $messagePrefix . '.getShortName()');
        self::assertSame($native->getStartLine(), $typhoon->getStartLine(), $messagePrefix . '.getStartLine()');
        self::assertSame($native->getStaticVariables(), $typhoon->getStaticVariables(), $messagePrefix . '.getStaticVariables()');
        self::assertTypeEquals($native->getReturnType(), $typhoon->getReturnType(), $messagePrefix . '.getReturnType()');
        self::assertTypeEquals($native->getTentativeReturnType(), $typhoon->getTentativeReturnType(), $messagePrefix . '.getTentativeReturnType()');
        if (method_exists(\ReflectionMethod::class, 'hasPrototype')) {
            /** @psalm-suppress MixedArgument, UnusedPsalmSuppress */
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
    private static function assertParametersEqual(array $native, array $typhoon, string $messagePrefix, bool $assertType = true): void
    {
        self::assertReflectionsEqual($native, $typhoon, $messagePrefix);

        foreach ($native as $index => $parameter) {
            self::assertParameterEquals($parameter, $typhoon[$index], $messagePrefix . "[{$index} ({$parameter->name})]", $assertType);
        }
    }

    private static function assertParameterEquals(\ReflectionParameter $native, \ReflectionParameter $typhoon, string $messagePrefix, bool $assertType = true): void
    {
        self::assertTrue(isset($typhoon->name), "isset({$messagePrefix}.name)");
        self::assertSame($native->name, $typhoon->name, $messagePrefix . '.name');
        self::assertSame($native->__toString(), $typhoon->__toString(), $messagePrefix . '.__toString()');
        self::assertSame($native->allowsNull(), $typhoon->allowsNull(), $messagePrefix . '.allowsNull()');
        self::assertSame($native->canBePassedByValue(), $typhoon->canBePassedByValue(), $messagePrefix . '.canBePassedByValue()');
        self::assertGetAttributes($native, $typhoon, $messagePrefix);
        // getClass() deprecated
        self::assertSame(self::reflectionToString($native->getDeclaringFunction()), self::reflectionToString($typhoon->getDeclaringFunction()), $messagePrefix . '.getDeclaringFunction()');
        self::assertSame($native->getDeclaringClass()?->name, $typhoon->getDeclaringClass()?->name, $messagePrefix . '.getDeclaringClass().name');
        if ($native->isDefaultValueAvailable()) {
            self::assertSame($native->getDefaultValueConstantName(), $typhoon->getDefaultValueConstantName(), $messagePrefix . '.getDefaultValueConstantName()');
        }
        self::assertSame($native->getName(), $typhoon->getName(), $messagePrefix . '.getName()');
        self::assertSame($native->getPosition(), $typhoon->getPosition(), $messagePrefix . '.getPosition()');
        if ($assertType) {
            self::assertTypeEquals($native->getType(), $typhoon->getType(), $messagePrefix . '.getType()');
        }
        self::assertSame($native->hasType(), $typhoon->hasType(), $messagePrefix . '.hasType()');
        // isArray() deprecated
        // isCallable() deprecated
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

    private static function assertGetAttributes(
        \ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter $native,
        \ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter $typhoon,
        string $messagePrefix,
    ): void {
        self::assertAttributesEqual($native->getAttributes(), $typhoon->getAttributes(), $messagePrefix . '.getAttributes()');
        self::assertAttributesEqual($native->getAttributes(Attr::class), $typhoon->getAttributes(Attr::class), $messagePrefix . '.getAttributes(Attr)');
        self::assertAttributesEqual(
            $native->getAttributes(Attr::class, \ReflectionAttribute::IS_INSTANCEOF),
            $typhoon->getAttributes(Attr::class, \ReflectionAttribute::IS_INSTANCEOF),
            $messagePrefix . '.getAttributes(Attr, IS_INSTANCEOF)',
        );
        self::assertAttributesEqual(
            $native->getAttributes(\Stringable::class, \ReflectionAttribute::IS_INSTANCEOF),
            $typhoon->getAttributes(\Stringable::class, \ReflectionAttribute::IS_INSTANCEOF),
            $messagePrefix . '.getAttributes(Stringable, IS_INSTANCEOF)',
        );
    }

    /**
     * @param array<\ReflectionAttribute> $native
     * @param array<\ReflectionAttribute> $typhoon
     */
    private static function assertAttributesEqual(array $native, array $typhoon, string $messagePrefix): void
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

    private static function assertMethodClosureEquals(\Closure $native, \Closure $typhoon, string $messagePrefix): void
    {
        $nativeReflection = new \ReflectionFunction($native);
        $typhoonReflection = new \ReflectionFunction($typhoon);

        self::assertSame($nativeReflection->isStatic(), $typhoonReflection->isStatic(), $messagePrefix . '.isStatic()');
        self::assertSame($nativeReflection->getClosureCalledClass()?->name, $typhoonReflection->getClosureCalledClass()?->name, $messagePrefix . '.getClosureCalledClass()');
        self::assertSame($nativeReflection->getClosureScopeClass()?->name, $typhoonReflection->getClosureScopeClass()?->name, $messagePrefix . '.getClosureCalledClass()');
        self::assertSame($nativeReflection->getClosureThis(), $typhoonReflection->getClosureThis(), $messagePrefix . '.getClosureThis()');
        // TODO remove assertType when functions ready
        self::assertParametersEqual($nativeReflection->getParameters(), $typhoonReflection->getParameters(), $messagePrefix . '.getParameters()', assertType: false);
    }

    /**
     * @param array<\ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter> $nativeReflections
     * @param array<\ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter> $typhoonReflections
     */
    private static function assertReflectionsEqual(array $nativeReflections, array $typhoonReflections, string $message): void
    {
        self::assertSame(
            array_map(self::reflectionToString(...), $nativeReflections),
            array_map(self::reflectionToString(...), $typhoonReflections),
            $message,
        );
    }

    /**
     * @param array<\ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter> $nativeReflections
     * @param array<\ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter> $typhoonReflections
     */
    private static function assertReflectionsEqualNoOrder(array $nativeReflections, array $typhoonReflections, string $message): void
    {
        $nativeReflectionStrings = array_map(self::reflectionToString(...), $nativeReflections);
        sort($nativeReflectionStrings);
        $typhoonReflectionStrings = array_map(self::reflectionToString(...), $typhoonReflections);
        sort($typhoonReflectionStrings);

        self::assertSame($nativeReflectionStrings, $typhoonReflectionStrings, $message);
    }

    /**
     * @param list<class-string> $native
     * @param list<class-string> $typhoon
     */
    private static function assertInterfaceNamesEqualNoOrder(array $native, array $typhoon, string $message): void
    {
        sort($native);
        sort($typhoon);

        self::assertSame($native, $typhoon, $message);
    }

    /**
     * @return non-empty-string
     */
    private static function reflectionToString(\ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter $reflection): string
    {
        return Id::fromReflection($reflection)->describe();
    }

    private static function assertTypeEquals(?\ReflectionType $native, ?\ReflectionType $typhoon, string $messagePrefix): void
    {
        self::assertSame(self::normalizeType($native), self::normalizeType($typhoon), $messagePrefix);
    }

    /**
     * @return ($type is null ? null : array)
     */
    private static function normalizeType(?\ReflectionType $type): ?array
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof \ReflectionNamedType) {
            return [
                'type' => 'named',
                'getName' => $type->getName(),
                'isBuiltin' => $type->isBuiltin(),
                'allowsNull' => $type->allowsNull(),
                '__toString()' => $type->__toString(),
            ];
        }

        \assert($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType);

        $normalizedTypes = array_map(self::normalizeType(...), $type->getTypes());
        sort($normalizedTypes);

        return [
            'type' => $type instanceof \ReflectionUnionType ? 'union' : 'intersection',
            'types' => $normalizedTypes,
            'allowsNull' => $type->allowsNull(),
        ];
    }

    /**
     * @return \Generator<int, class-string>
     * @psalm-suppress MoreSpecificReturnType
     */
    private static function getClasses(\ReflectionClass $class): \Generator
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

        yield (new class {})::class;
        yield (new class extends \stdClass {})::class;
        yield \Traversable::class;
        yield \Iterator::class;
        yield \IteratorAggregate::class;
        yield \Generator::class;
        yield \FilterIterator::class;
        yield \ArrayAccess::class;
        yield \Throwable::class;
        yield \Error::class;
        yield \Exception::class;
        yield \UnitEnum::class;
        yield \BackedEnum::class;
        yield Variance::class;
        yield \stdClass::class;
        yield Trait1::class;
        yield \Closure::class;
        yield \DateTimeImmutable::class;
        yield \DateTimeImmutable::class;
    }

    /**
     * @return \Generator<object>
     */
    private static function getObjects(\ReflectionClass $class): \Generator
    {
        try {
            yield $class->newInstance();
        } catch (\Throwable) {
            try {
                yield $class->newInstanceWithoutConstructor();
            } catch (\Throwable) {
            }
        }

        yield static fn(): int => 1;
        yield (static fn(): \Generator => yield 1)();
        yield new class {};
        yield new class extends \stdClass {};
        yield new class {
            public function __toString(): string
            {
                return '';
            }
        };
        yield new \stdClass();
        yield new \DateTimeImmutable();
        yield new \DateTimeImmutable();
        yield new \ArrayObject();
        yield new \ArrayIterator();
        yield new \Error();
        yield new \Exception();
        yield Variance::Invariant;
    }

    private static function assertResultOrExceptionEqual(\Closure $native, \Closure $typhoon, string $messagePrefix): void
    {
        $nativeException = null;
        $nativeResult = null;
        $typhoonException = null;
        $typhoonResult = null;

        try {
            $nativeResult = $native();
        } catch (\Throwable $nativeException) {
        }

        try {
            $typhoonResult = $typhoon();
        } catch (\Throwable $typhoonException) {
        }

        if ($nativeException !== null) {
            $messagePrefix .= '.exception';
            self::assertInstanceOf($nativeException::class, $typhoonException, $messagePrefix . '.class');
            self::assertSame($nativeException->getMessage(), $typhoonException->getMessage(), $messagePrefix . '.getMessage()');
            self::assertEquals($nativeException->getPrevious(), $typhoonException->getPrevious(), $messagePrefix . '.getPrevious()');
            self::assertSame($nativeException->getCode(), $typhoonException->getCode(), $messagePrefix . '.getCode()');

            return;
        }

        self::assertNull($typhoonException, $messagePrefix);
        self::assertEquals($nativeResult, $typhoonResult, $messagePrefix);
    }
}
