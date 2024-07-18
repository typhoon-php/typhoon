<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassConstReflection;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\Exception\DeclarationNotFound;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Kind;
use Typhoon\Reflection\MethodReflection;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant T of object
 * @extends \ReflectionClass<T>
 * @property-read class-string<T> $name
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ClassAdapter extends \ReflectionClass
{
    public const IS_READONLY = 65536;

    public static function normalizeNameForException(string $name): string
    {
        $nullBytePosition = strpos($name, "\0");

        if ($nullBytePosition === false) {
            return $name;
        }

        return substr($name, 0, $nullBytePosition);
    }

    /**
     * @param ClassReflection<T, NamedClassId<class-string<T>>|AnonymousClassId<?class-string<T>>> $reflection
     */
    private function __construct(
        private readonly ClassReflection $reflection,
        private readonly TyphoonReflector $reflector,
    ) {
        unset($this->name);
    }

    /**
     * @template TObject of object
     * @param ClassReflection<TObject, NamedClassId<class-string<TObject>>|AnonymousClassId<?class-string<TObject>>> $reflection
     * @return \ReflectionClass<TObject>
     */
    public static function create(ClassReflection $reflection, TyphoonReflector $reflector): \ReflectionClass
    {
        $adapter = new self($reflection, $reflector);

        if ($reflection->isEnum()) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             * @var \ReflectionClass<TObject>
             */
            return new EnumAdapter($adapter, $reflection);
        }

        return $adapter;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->getName(),
            default => new \LogicException(sprintf('Undefined property %s::$%s', self::class, $name)),
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'name';
    }

    public function __toString(): string
    {
        $this->loadNative();

        return parent::__toString();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::fromList($this->reflection->attributes(), $name, $flags);
    }

    public function getConstant(string $name): mixed
    {
        return isset($this->reflection->consts()[$name])
            ? $this->reflection->consts()[$name]->value()
            : false;
    }

    public function getConstants(?int $filter = null): array
    {
        return $this
            ->reflection
            ->consts()
            ->filter(static fn(ClassConstReflection $constant): bool => $filter === null || ($constant->native()->getModifiers() & $filter) !== 0)
            ->map(static fn(ClassConstReflection $constant): mixed => $constant->value())
            ->toArray();
    }

    public function getConstructor(): ?\ReflectionMethod
    {
        return ($this->reflection->methods()['__construct'] ?? null)?->native();
    }

    public function getDefaultProperties(): array
    {
        return $this
            ->reflection
            ->properties()
            ->filter(static fn(PropertyReflection $property): bool => $property->hasDefaultValue())
            ->map(static fn(PropertyReflection $property): mixed => $property->defaultValue())
            ->toArray();
    }

    public function getDocComment(): string|false
    {
        return $this->reflection->phpDoc() ?? false;
    }

    public function getEndLine(): int|false
    {
        return $this->reflection->endLine() ?? false;
    }

    public function getExtension(): ?\ReflectionExtension
    {
        $extension = $this->reflection->extension();

        if ($extension === null) {
            return null;
        }

        return new \ReflectionExtension($extension);
    }

    public function getExtensionName(): string|false
    {
        return $this->reflection->extension() ?? false;
    }

    public function getFileName(): string|false
    {
        return $this->reflection->file() ?? false;
    }

    public function getInterfaceNames(): array
    {
        return array_keys($this->reflection->data[Data::Interfaces]);
    }

    public function getInterfaces(): array
    {
        $interfaces = $this->getInterfaceNames();

        return array_combine($interfaces, array_map(
            fn(string $name): \ReflectionClass => $this->reflector->reflect(Id::namedClass($name))->native(),
            $interfaces,
        ));
    }

    public function getMethod(string $name): \ReflectionMethod
    {
        return $this->reflection->methods()[$name]->native();
    }

    public function getMethods(?int $filter = null): array
    {
        return $this
            ->reflection
            ->methods()
            ->map(static fn(MethodReflection $method): \ReflectionMethod => $method->native())
            ->filter(static fn(\ReflectionMethod $method): bool => $filter === null || ($method->getModifiers() & $filter) !== 0)
            ->toList();
    }

    public function getModifiers(): int
    {
        if ($this->isEnum()) {
            return self::IS_FINAL;
        }

        /** @var int-mask-of<\ReflectionClass::IS_*> */
        return ($this->reflection->isAbstract() ? self::IS_EXPLICIT_ABSTRACT : 0)
            | ($this->isFinal() ? self::IS_FINAL : 0)
            | ($this->isReadonly() ? self::IS_READONLY : 0);
    }

    public function getName(): string
    {
        return $this->reflection->id->name ?? throw new \ReflectionException(sprintf(
            'Runtime name of anonymous class %s is not available',
            $this->reflection->id->describe(),
        ));
    }

    public function getNamespaceName(): string
    {
        $name = $this->reflection->id->name;

        if ($name === null) {
            return '';
        }

        $lastSlashPosition = strrpos($name, '\\');

        if ($lastSlashPosition === false) {
            return '';
        }

        return substr($name, 0, $lastSlashPosition);
    }

    public function getParentClass(): \ReflectionClass|false
    {
        return $this->reflection->parent()?->native() ?? false;
    }

    public function getProperties(?int $filter = null): array
    {
        return $this
            ->reflection
            ->properties()
            ->map(static fn(PropertyReflection $property): \ReflectionProperty => $property->native())
            ->filter(static fn(\ReflectionProperty $property): bool => $filter === null || ($property->getModifiers() & $filter) !== 0)
            ->toList();
    }

    public function getProperty(string $name): \ReflectionProperty
    {
        return $this->reflection->properties()[$name]->native();
    }

    public function getReflectionConstant(string $name): \ReflectionClassConstant|false
    {
        return ($this->reflection->consts()[$name] ?? null)?->native() ?? false;
    }

    public function getReflectionConstants(?int $filter = null): array
    {
        return $this
            ->reflection
            ->consts()
            ->map(static fn(ClassConstReflection $constant): \ReflectionClassConstant => $constant->native())
            ->filter(static fn(\ReflectionClassConstant $constant): bool => $filter === null || ($constant->getModifiers() & $filter) !== 0)
            ->toList();
    }

    public function getShortName(): string
    {
        $name = $this->reflection->id->name;

        if ($name === null) {
            return '{anonymous-class}';
        }

        $lastSlashPosition = strrpos($name, '\\');

        if ($lastSlashPosition === false) {
            return $name;
        }

        $shortName = substr($name, $lastSlashPosition + 1);
        \assert($shortName !== '', 'A valid class name must not end with a backslash');

        return $shortName;
    }

    public function getStartLine(): int|false
    {
        return $this->reflection->startLine() ?? false;
    }

    public function getStaticProperties(): array
    {
        $this->loadNative();

        return parent::getStaticProperties();
    }

    public function getStaticPropertyValue(string $name, mixed $default = null): mixed
    {
        $this->loadNative();

        return parent::getStaticPropertyValue($name, $default);
    }

    public function getTraitAliases(): array
    {
        return $this->reflection->data[NativeTraitInfoKey::Key]->aliases;
    }

    public function getTraitNames(): array
    {
        /** @var list<trait-string> */
        return $this->reflection->data[NativeTraitInfoKey::Key]->names;
    }

    public function getTraits(): array
    {
        $traits = [];

        foreach ($this->getTraitNames() as $name) {
            $traits[$name] = $this->reflector->reflect(Id::namedClass($name))->native();
        }

        return $traits;
    }

    public function hasConstant(string $name): bool
    {
        return isset($this->reflection->consts()[$name]);
    }

    public function hasMethod(string $name): bool
    {
        return isset($this->reflection->methods()[$name]);
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->reflection->properties()[$name]);
    }

    public function implementsInterface(string|\ReflectionClass $interface): bool
    {
        if (\is_string($interface)) {
            try {
                $interfaceId = Id::class($interface);
            } catch (\InvalidArgumentException) {
                throw new \ReflectionException(sprintf('Interface "%s" does not exist', self::normalizeNameForException($interface)));
            }

            if ($interfaceId instanceof AnonymousClassId) {
                throw new \ReflectionException(sprintf('%s is not an interface', self::normalizeNameForException($interface)));
            }

            try {
                $interfaceReflection = $this->reflector->reflect($interfaceId)->native();
            } catch (DeclarationNotFound) {
                throw new \ReflectionException(sprintf('Interface "%s" does not exist', self::normalizeNameForException($interface)));
            }
        } else {
            $interfaceReflection = $interface;
        }

        if (!$interfaceReflection->isInterface()) {
            throw new \ReflectionException(sprintf('%s is not an interface', self::normalizeNameForException($interfaceReflection->name)));
        }

        return $this->reflection->isInstanceOf($interfaceReflection->name);
    }

    public function inNamespace(): bool
    {
        return str_contains($this->name, '\\');
    }

    public function isAbstract(): bool
    {
        if ($this->reflection->isAbstract()) {
            return true;
        }

        if ($this->reflection->isInterface() || $this->reflection->isTrait()) {
            return $this->reflection->methods()->any(static fn(MethodReflection $method): bool => $method->isAbstract());
        }

        return false;
    }

    public function isAnonymous(): bool
    {
        return $this->reflection->isAnonymous();
    }

    public function isCloneable(): bool
    {
        return $this->reflection->kind() === ClassKind::Class_
            && !$this->reflection->isAbstract()
            && (!isset($this->reflection->methods()['__clone']) || $this->reflection->methods()['__clone']->isPublic());
    }

    public function isEnum(): bool
    {
        return $this->reflection->isEnum();
    }

    public function isFinal(): bool
    {
        return $this->reflection->isFinal(Kind::Native);
    }

    public function isInstance(object $object): bool
    {
        if ($this->isInterface()) {
            return isset(class_implements($object)[$this->name]);
        }

        return $object::class === $this->name || isset(class_parents($object)[$this->name]);
    }

    public function isInstantiable(): bool
    {
        return $this->reflection->kind() === ClassKind::Class_
            && !$this->reflection->isAbstract()
            && (($this->reflection->methods()['__construct'] ?? null)?->isPublic() ?? true);
    }

    public function isInterface(): bool
    {
        return $this->reflection->isInterface();
    }

    public function isInternal(): bool
    {
        return $this->reflection->isInternallyDefined();
    }

    public function isIterable(): bool
    {
        return ($this->reflection->kind() === ClassKind::Class_ || $this->reflection->kind() === ClassKind::Enum)
            && !$this->reflection->isAbstract()
            && $this->reflection->isInstanceOf(\Traversable::class);
    }

    public function isIterateable(): bool
    {
        return $this->isIterable();
    }

    public function isReadonly(): bool
    {
        return $this->reflection->isReadonly(Kind::Native);
    }

    public function isSubclassOf(string|\ReflectionClass $class): bool
    {
        if (\is_string($class)) {
            if ($class === $this->name) {
                return false;
            }

            try {
                $classId = Id::class($class);
            } catch (\InvalidArgumentException) {
                throw new \ReflectionException(sprintf('Class "%s" does not exist', self::normalizeNameForException($class)));
            }

            try {
                $this->reflector->reflect($classId);
            } catch (DeclarationNotFound) {
                throw new \ReflectionException(sprintf('Class "%s" does not exist', self::normalizeNameForException($class)));
            }

            return $this->reflection->isInstanceOf($class);
        }

        return $class->name !== $this->name && $this->reflection->isInstanceOf($class->name);
    }

    public function isTrait(): bool
    {
        return $this->reflection->isTrait();
    }

    public function isUserDefined(): bool
    {
        return !$this->isInternal();
    }

    public function newInstance(mixed ...$args): object
    {
        $this->loadNative();

        return parent::newInstanceArgs($args);
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function newInstanceArgs(array $args = []): object
    {
        $this->loadNative();

        return parent::newInstanceArgs($args);
    }

    public function newInstanceWithoutConstructor(): object
    {
        $this->loadNative();

        return parent::newInstanceWithoutConstructor();
    }

    public function setStaticPropertyValue(string $name, mixed $value): void
    {
        $this->loadNative();

        parent::setStaticPropertyValue($name, $value);
    }

    private bool $nativeLoaded = false;

    private function loadNative(): void
    {
        if ($this->nativeLoaded) {
            return;
        }

        $class = $this->reflection->id->name ?? throw new \LogicException(sprintf(
            "Cannot natively reflect %s, because it's runtime name is not available",
            $this->reflection->id->describe(),
        ));

        parent::__construct($class);
        $this->nativeLoaded = true;
    }
}
