<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\ClassReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant TEnum of \UnitEnum
 * @property-read class-string<TEnum> $name
 * @extends \ReflectionEnum<TEnum>
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class EnumAdapter extends \ReflectionEnum
{
    public const IS_READONLY = ClassAdapter::IS_READONLY;

    /**
     * @param ClassAdapter<TEnum> $_class
     * @param ClassReflection<TEnum, NamedClassId<class-string<TEnum>>> $reflection
     */
    public function __construct(
        private readonly ClassAdapter $_class,
        private readonly ClassReflection $reflection,
    ) {
        unset($this->name);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name): mixed
    {
        return $this->_class->__get($name);
    }

    public function __isset(string $name): bool
    {
        return $this->_class->__isset($name);
    }

    public function __toString(): string
    {
        return $this->_class->__toString();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return $this->_class->getAttributes($name, $flags);
    }

    public function getBackingType(): ?\ReflectionNamedType
    {
        /** @var ?\ReflectionNamedType */
        return $this->reflection->enumBackingType()?->accept(new ToNativeTypeConverter());
    }

    public function getCase(string $name): \ReflectionEnumUnitCase
    {
        if ($name === '') {
            throw new \ReflectionException(\sprintf('Case %s:: does not exist', $this->name));
        }

        $case = ($this->reflection->enumCases()[$name] ?? null)
            ?->toNativeReflection()
            ?? throw new \ReflectionException(\sprintf('Case %s::%s does not exist', $this->name, $name));
        \assert($case instanceof \ReflectionEnumUnitCase);

        return $case;
    }

    public function getCases(): array
    {
        return $this
            ->reflection
            ->enumCases()
            ->map(static function (ClassConstantReflection $constant): \ReflectionEnumUnitCase {
                $native = $constant->toNativeReflection();
                \assert($native instanceof \ReflectionEnumUnitCase);

                return $native;
            })
            ->toList();
    }

    public function getConstant(string $name): mixed
    {
        return $this->_class->getConstant($name);
    }

    public function getConstants(?int $filter = null): array
    {
        return $this->_class->getConstants($filter);
    }

    public function getConstructor(): ?\ReflectionMethod
    {
        return $this->_class->getConstructor();
    }

    public function getDefaultProperties(): array
    {
        return $this->_class->getDefaultProperties();
    }

    public function getDocComment(): string|false
    {
        return $this->_class->getDocComment();
    }

    public function getEndLine(): int|false
    {
        return $this->_class->getEndLine();
    }

    public function getExtension(): ?\ReflectionExtension
    {
        return $this->_class->getExtension();
    }

    public function getExtensionName(): string|false
    {
        return $this->_class->getExtensionName();
    }

    public function getFileName(): string|false
    {
        return $this->_class->getFileName();
    }

    public function getInterfaceNames(): array
    {
        return $this->_class->getInterfaceNames();
    }

    public function getInterfaces(): array
    {
        return $this->_class->getInterfaces();
    }

    public function getMethod(string $name): \ReflectionMethod
    {
        return $this->_class->getMethod($name);
    }

    public function getMethods(?int $filter = null): array
    {
        return $this->_class->getMethods($filter);
    }

    public function getModifiers(): int
    {
        return $this->_class->getModifiers();
    }

    public function getName(): string
    {
        return $this->_class->name;
    }

    public function getNamespaceName(): string
    {
        return $this->_class->getNamespaceName();
    }

    public function getParentClass(): \ReflectionClass|false
    {
        return $this->_class->getParentClass();
    }

    public function getProperties(?int $filter = null): array
    {
        return $this->_class->getProperties($filter);
    }

    public function getProperty(string $name): \ReflectionProperty
    {
        return $this->_class->getProperty($name);
    }

    public function getReflectionConstant(string $name): \ReflectionClassConstant|false
    {
        return $this->_class->getReflectionConstant($name);
    }

    public function getReflectionConstants(?int $filter = null): array
    {
        return $this->_class->getReflectionConstants($filter);
    }

    public function getShortName(): string
    {
        return $this->_class->getShortName();
    }

    public function getStartLine(): int|false
    {
        return $this->_class->getStartLine();
    }

    public function getStaticProperties(): array
    {
        return $this->_class->getStaticProperties();
    }

    public function getStaticPropertyValue(string $name, mixed $default = null): mixed
    {
        return $this->_class->getStaticPropertyValue($name, $default);
    }

    public function getTraitAliases(): array
    {
        return $this->_class->getTraitAliases();
    }

    public function getTraitNames(): array
    {
        return $this->_class->getTraitNames();
    }

    public function getTraits(): array
    {
        return $this->_class->getTraits();
    }

    public function hasCase(string $name): bool
    {
        return $name !== '' && $this->reflection->enumCases()->offsetExists($name);
    }

    public function hasConstant(string $name): bool
    {
        return $this->_class->hasConstant($name);
    }

    public function hasMethod(string $name): bool
    {
        return $this->_class->hasMethod($name);
    }

    public function hasProperty(string $name): bool
    {
        return $this->_class->hasProperty($name);
    }

    public function implementsInterface(string|\ReflectionClass $interface): bool
    {
        return $this->_class->implementsInterface($interface);
    }

    public function inNamespace(): bool
    {
        return $this->_class->inNamespace();
    }

    public function isAbstract(): bool
    {
        return $this->_class->isAbstract();
    }

    public function isAnonymous(): bool
    {
        return $this->_class->isAnonymous();
    }

    public function isBacked(): bool
    {
        return $this->reflection->isBackedEnum();
    }

    public function isCloneable(): bool
    {
        return $this->_class->isCloneable();
    }

    public function isEnum(): bool
    {
        return $this->_class->isEnum();
    }

    public function isFinal(): bool
    {
        return $this->_class->isFinal();
    }

    public function isInstance(object $object): bool
    {
        return $this->_class->isInstance($object);
    }

    public function isInstantiable(): bool
    {
        return $this->_class->isInstantiable();
    }

    public function isInterface(): bool
    {
        return $this->_class->isInterface();
    }

    public function isInternal(): bool
    {
        return $this->_class->isInternal();
    }

    public function isIterable(): bool
    {
        return $this->_class->isIterable();
    }

    public function isIterateable(): bool
    {
        return $this->_class->isIterateable();
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, UnusedPsalmSuppress
     */
    public function isReadonly(): bool
    {
        return $this->_class->isReadonly();
    }

    public function isSubclassOf(string|\ReflectionClass $class): bool
    {
        return $this->_class->isSubclassOf($class);
    }

    public function isTrait(): bool
    {
        return $this->_class->isTrait();
    }

    public function isUserDefined(): bool
    {
        return $this->_class->isUserDefined();
    }

    public function newInstance(mixed ...$args): object
    {
        return $this->_class->newInstance(...$args);
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function newInstanceArgs(array $args = []): object
    {
        return $this->_class->newInstanceArgs($args);
    }

    public function newInstanceWithoutConstructor(): object
    {
        return $this->_class->newInstanceWithoutConstructor();
    }

    public function setStaticPropertyValue(string $name, mixed $value): void
    {
        $this->_class->setStaticPropertyValue($name, $value);
    }
}
