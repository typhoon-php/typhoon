<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Attributes\AttributeReflections;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Inheritance\MethodsInheritanceResolver;
use Typhoon\Reflection\Inheritance\PropertiesInheritanceResolver;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Type\Type;

/**
 * @api
 * @template-covariant T of object
 * @extends \ReflectionClass<T>
 * @property-read class-string<T> $name
 * @psalm-suppress PropertyNotSetInConstructor, MissingImmutableAnnotation
 */
final class ClassReflection extends \ReflectionClass
{
    public const IS_READONLY = 65536;

    private ?AttributeReflections $attributes = null;

    private bool $nativeLoaded = false;

    /**
     * @var ?array<non-empty-string, MethodReflection>
     */
    private ?array $resolvedMethods = null;

    /**
     * @var ?array<non-empty-string, PropertyReflection>
     */
    private ?array $resolvedProperties = null;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param ClassMetadata<T> $metadata
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
        private readonly ClassMetadata $metadata,
    ) {
        unset($this->name);
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->metadata->name,
            default => new \OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name)),
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

    /**
     * @template TClass as object
     * @param class-string<TClass>|null $name
     * @return ($name is null ? list<AttributeReflection<object>> : list<AttributeReflection<TClass>>)
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($this->attributes === null) {
            $class = $this->metadata->name;
            $this->attributes = new AttributeReflections(
                $this->classReflector,
                $this->metadata->attributes,
                static fn(): array => (new \ReflectionClass($class))->getAttributes(),
            );
        }

        return $this->attributes->get($name, $flags);
    }

    public function getConstant(string $name): mixed
    {
        $this->loadNative();

        return parent::getConstant($name);
    }

    public function getConstants(?int $filter = null): array
    {
        $this->loadNative();

        return parent::getConstants($filter);
    }

    public function getConstructor(): ?MethodReflection
    {
        return $this->getResolvedMethods()['__construct'] ?? null;
    }

    public function getDefaultProperties(): array
    {
        $this->loadNative();

        return parent::getDefaultProperties();
    }

    public function getDocComment(): string|false
    {
        return $this->metadata->docComment;
    }

    public function getEndLine(): int|false
    {
        return $this->metadata->endLine;
    }

    public function getExtension(): ?\ReflectionExtension
    {
        if ($this->metadata->extension === false) {
            return null;
        }

        return new \ReflectionExtension($this->metadata->extension);
    }

    public function getExtensionName(): string|false
    {
        return $this->metadata->extension;
    }

    public function getFileName(): string|false
    {
        return $this->metadata->file;
    }

    public function getInterfaceNames(): array
    {
        return array_keys($this->getInterfaces());
    }

    /**
     * @return array<interface-string, self>
     */
    public function getInterfaces(): array
    {
        $interfaces = [];
        $ancestors = [];

        foreach ($this->metadata->ownInterfaceTypes as $ownInterfaceType) {
            $ownInterface = $this->reflectClass($ownInterfaceType->class);
            $interfaces[$ownInterface->name] = $ownInterface;
            $ancestors[] = $ownInterface;
        }

        $parent = $this->getParentClass();

        if ($parent !== false) {
            $ancestors[] = $parent;
        }

        foreach ($ancestors as $ancestor) {
            foreach ($ancestor->getInterfaces() as $interface) {
                $interfaces[$interface->name] = $interface;
            }
        }

        return $interfaces;
    }

    public function getMethod(string $name): MethodReflection
    {
        return $this->getResolvedMethods()[$name] ?? throw new ReflectionException();
    }

    /**
     * @return list<MethodReflection>
     */
    public function getMethods(?int $filter = null): array
    {
        if ($filter === null || $filter === 0) {
            return array_values($this->getResolvedMethods());
        }

        return array_values(array_filter(
            $this->getResolvedMethods(),
            static fn(MethodReflection $method): bool => ($filter & $method->getModifiers()) !== 0,
        ));
    }

    public function getModifiers(): int
    {
        return $this->metadata->modifiers;
    }

    public function getName(): string
    {
        return $this->metadata->name;
    }

    public function getNamespaceName(): string
    {
        $lastSlashPosition = strrpos($this->metadata->name, '\\');

        if ($lastSlashPosition === false) {
            return '';
        }

        return substr($this->metadata->name, 0, $lastSlashPosition);
    }

    public function getParentClass(): self|false
    {
        if ($this->metadata->parentType === null) {
            return false;
        }

        return $this->reflectClass($this->metadata->parentType->class);
    }

    /**
     * @return list<self>
     */
    public function getParentClasses(): array
    {
        $parent = $this->getParentClass();

        if ($parent === false) {
            return [];
        }

        return [$parent, ...$parent->getParentClasses()];
    }

    /**
     * @return list<class-string>
     */
    public function getParentClassNames(): array
    {
        $parent = $this->getParentClass();

        if ($parent === false) {
            return [];
        }

        return [$parent->name, ...$parent->getParentClassNames()];
    }

    /**
     * @return list<PropertyReflection>
     */
    public function getProperties(?int $filter = null): array
    {
        if ($filter === null || $filter === 0) {
            return array_values($this->getResolvedProperties());
        }

        return array_values(array_filter(
            $this->getResolvedProperties(),
            static fn(PropertyReflection $property): bool => ($filter & $property->getModifiers()) !== 0,
        ));
    }

    public function getProperty(string $name): PropertyReflection
    {
        return $this->getResolvedProperties()[$name] ?? throw new ReflectionException();
    }

    public function getReflectionConstant(string $name): \ReflectionClassConstant|false
    {
        $this->loadNative();

        return parent::getReflectionConstant($name);
    }

    public function getReflectionConstants(?int $filter = null): array
    {
        $this->loadNative();

        return parent::getReflectionConstants($filter);
    }

    public function getShortName(): string
    {
        $lastSlashPosition = strrpos($this->metadata->name, '\\');

        if ($lastSlashPosition === false) {
            return $this->metadata->name;
        }

        /** @var non-empty-string */
        return substr($this->metadata->name, $lastSlashPosition + 1);
    }

    public function getStartLine(): int|false
    {
        return $this->metadata->startLine;
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

    public function getTemplate(int|string $nameOrPosition): TemplateReflection
    {
        if (\is_int($nameOrPosition)) {
            return $this->metadata->templates[$nameOrPosition] ?? throw new ReflectionException();
        }

        foreach ($this->metadata->templates as $template) {
            if ($template->name === $nameOrPosition) {
                return $template;
            }
        }

        throw new ReflectionException();
    }

    /**
     * @return list<TemplateReflection>
     */
    public function getTemplates(): array
    {
        return $this->metadata->templates;
    }

    public function getTraitAliases(): array
    {
        $this->loadNative();

        return parent::getTraitAliases();
    }

    public function getTraitNames(): array
    {
        $this->loadNative();

        return parent::getTraitNames();
    }

    public function getTraits(): array
    {
        $this->loadNative();

        return parent::getTraits();
    }

    public function getTypeAlias(string $name): Type
    {
        return $this->metadata->typeAliases[$name] ?? throw new ReflectionException();
    }

    /**
     * @return array<non-empty-string, Type>
     */
    public function getTypeAliases(): array
    {
        return $this->metadata->typeAliases;
    }

    public function hasConstant(string $name): bool
    {
        $this->loadNative();

        return parent::hasConstant($name);
    }

    public function hasMethod(string $name): bool
    {
        return isset($this->getResolvedMethods()[$name]);
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->getResolvedProperties()[$name]);
    }

    public function implementsInterface(string|\ReflectionClass $interface): bool
    {
        if (\is_string($interface)) {
            $interface = $this->reflectClass($interface);
        }

        if (!$interface->isInterface()) {
            throw new ReflectionException();
        }

        if ($this->metadata->name === $interface->name) {
            return true;
        }

        return \in_array($interface->name, $this->getInterfaceNames(), true);
    }

    public function inNamespace(): bool
    {
        return str_contains($this->metadata->name, '\\');
    }

    public function isAbstract(): bool
    {
        if ($this->metadata->interface) {
            return $this->getMethods() !== [];
        }

        if ($this->metadata->trait) {
            foreach ($this->getMethods() as $method) {
                if ($method->isAbstract()) {
                    return true;
                }
            }

            return false;
        }

        return ($this->metadata->modifiers & self::IS_EXPLICIT_ABSTRACT) !== 0;
    }

    public function isAnonymous(): bool
    {
        return $this->metadata->anonymous;
    }

    public function isCloneable(): bool
    {
        return !$this->isAbstract()
            && !$this->metadata->interface
            && !$this->metadata->trait
            && !$this->metadata->enum
            && (!$this->hasMethod('__clone') || $this->getMethod('__clone')->isPublic());
    }

    public function isDeprecated(): bool
    {
        return $this->metadata->deprecated;
    }

    public function isEnum(): bool
    {
        return $this->metadata->enum;
    }

    public function isFinal(): bool
    {
        return $this->metadata->enum || ($this->metadata->modifiers & self::IS_FINAL) !== 0;
    }

    public function isInstance(object $object): bool
    {
        return $object instanceof $this->metadata->name;
    }

    public function isInstantiable(): bool
    {
        return !$this->isAbstract()
            && !$this->metadata->interface
            && !$this->metadata->trait
            && !$this->metadata->enum
            && (!$this->hasMethod('__construct') || $this->getMethod('__construct')->isPublic());
    }

    public function isInterface(): bool
    {
        return $this->metadata->interface;
    }

    public function isInternal(): bool
    {
        return $this->metadata->internal;
    }

    public function isIterable(): bool
    {
        return !$this->metadata->interface
            && !$this->isAbstract()
            && $this->implementsInterface(\Traversable::class);
    }

    public function isIterateable(): bool
    {
        return $this->isIterable();
    }

    public function isReadOnly(): bool
    {
        return ($this->metadata->modifiers & self::IS_READONLY) !== 0;
    }

    public function isSubclassOf(string|\ReflectionClass $class): bool
    {
        if (\is_string($class)) {
            $class = $this->reflectClass($class);
        }

        if ($class->isInterface() && $this->implementsInterface($class)) {
            return true;
        }

        if ($class->name === $this->metadata->name) {
            return true;
        }

        return \in_array($class->name, $this->getParentClassNames(), true);
    }

    public function isTrait(): bool
    {
        return $this->metadata->trait;
    }

    public function isUserDefined(): bool
    {
        return !$this->isInternal();
    }

    public function newInstance(mixed ...$args): object
    {
        $this->loadNative();

        return parent::newInstance(...$args);
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

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    private function getResolvedMethods(): array
    {
        if ($this->resolvedMethods !== null) {
            return $this->resolvedMethods;
        }

        $resolver = new MethodsInheritanceResolver($this->classReflector);
        $resolver->setOwn($this->metadata->ownMethods);
        $resolver->addInherited(...$this->metadata->ownInterfaceTypes);
        $resolver->addInherited(...$this->metadata->ownTraitTypes);

        if ($this->metadata->parentType !== null) {
            $resolver->addInherited($this->metadata->parentType);
        }

        return $this->resolvedMethods = array_map(
            fn(MethodMetadata $metadata): MethodReflection => new MethodReflection($this->classReflector, $metadata),
            $resolver->resolve(),
        );
    }

    /**
     * @return array<non-empty-string, PropertyReflection>
     */
    private function getResolvedProperties(): array
    {
        if ($this->resolvedProperties !== null) {
            return $this->resolvedProperties;
        }

        $resolver = new PropertiesInheritanceResolver($this->classReflector);
        $resolver->setOwn($this->metadata->ownProperties);
        $resolver->addInherited(...$this->metadata->ownTraitTypes);

        if ($this->metadata->parentType !== null) {
            $resolver->addInherited($this->metadata->parentType);
        }

        return $this->resolvedProperties = array_map(
            fn(PropertyMetadata $metadata): PropertyReflection => new PropertyReflection($this->classReflector, $metadata),
            $resolver->resolve(),
        );
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            parent::__construct($this->metadata->name);
            $this->nativeLoaded = true;
        }
    }

    /**
     * @template TObject of object
     * @param class-string<TObject> $class
     * @return ClassReflection<TObject>
     */
    private function reflectClass(string $class): self
    {
        return $this->classReflector->reflectClass($class);
    }
}
