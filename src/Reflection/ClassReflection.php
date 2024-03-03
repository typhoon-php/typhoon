<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\AttributeReflection\AttributeReflections;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Exception\InterfaceDoesNotExist;
use Typhoon\Reflection\Exception\MethodDoesNotExist;
use Typhoon\Reflection\Exception\NotAnInterface;
use Typhoon\Reflection\Exception\PropertyDoesNotExist;
use Typhoon\Reflection\Exception\TemplateDoesNotExist;
use Typhoon\Reflection\Exception\TypeAliasDoesNotExist;
use Typhoon\Reflection\Metadata\ClassConstantMetadata;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\TypeResolver\TemplateResolver;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\TypeVisitor;

/**
 * @api
 * @template-covariant T of object
 * @extends \ReflectionClass<T>
 * @property-read class-string<T> $name
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ClassReflection extends \ReflectionClass
{
    public const IS_READONLY = 65536;

    private ?AttributeReflections $attributes = null;

    private bool $nativeLoaded = false;

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

    /**
     * @param array<Type> $templateArguments
     * @return TypeVisitor<Type>
     */
    public function createTypeResolver(array $templateArguments = [], bool $resolveStatic = false): TypeVisitor
    {
        return new TemplateResolver(
            templateArguments: TemplateResolver::prepareTemplateArguments($this->getTemplates(), $templateArguments),
            self: $this->name,
            resolveStatic: $resolveStatic,
        );
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
            $this->attributes = AttributeReflections::create(
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
        return iterator_to_array($this->yieldInterfaces());
    }

    public function getMethod(string $name): MethodReflection
    {
        return $this->getResolvedMethods()[$name] ?? throw new MethodDoesNotExist($this->name, $name);
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
        $parentClass = $this->metadata->parentClass();

        if ($parentClass === null) {
            return false;
        }

        return $this->reflectClass($parentClass);
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
        return $this->getResolvedProperties()[$name] ?? throw new PropertyDoesNotExist($this->name, $name);
    }

    public function getReflectionConstant(string $name): ClassConstantReflection|false
    {
        return $this->getResolvedConstants()[$name] ?? false;
    }

    /**
     * @return list<ClassConstantReflection>
     */
    public function getReflectionConstants(?int $filter = null): array
    {
        if ($filter === null || $filter === 0) {
            return array_values($this->getResolvedConstants());
        }

        return array_values(array_filter(
            $this->getResolvedConstants(),
            static fn(ClassConstantReflection $constant): bool => ($filter & $constant->getModifiers()) !== 0,
        ));
    }

    public function getShortName(): string
    {
        $lastSlashPosition = strrpos($this->metadata->name, '\\');

        if ($lastSlashPosition === false) {
            return $this->metadata->name;
        }

        $shortName = substr($this->metadata->name, $lastSlashPosition + 1);
        \assert($shortName !== '');

        return $shortName;
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

    /**
     * @throws TemplateDoesNotExist
     */
    public function getTemplate(int|string $nameOrPosition): TemplateReflection
    {
        if (\is_int($nameOrPosition)) {
            return $this->metadata->templates[$nameOrPosition]
                ?? throw new TemplateDoesNotExist(types::atClass($this->name), $nameOrPosition);
        }

        foreach ($this->metadata->templates as $template) {
            if ($template->name === $nameOrPosition) {
                return $template;
            }
        }

        throw new TemplateDoesNotExist(types::atClass($this->name), $nameOrPosition);
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
        $traitAliases = [];

        foreach ($this->metadata->traitMethodAliases as $trait => $methodAliases) {
            foreach ($methodAliases as $method => $aliases) {
                foreach ($aliases as $alias) {
                    if ($alias->alias === null) {
                        continue;
                    }

                    $traitAliases[$alias->alias] = $trait . '::' . $method;
                }
            }
        }

        return $traitAliases;
    }

    public function getTraitNames(): array
    {
        $traitNames = [];

        foreach ($this->yieldTraits() as $name => $_trait) {
            $traitNames[] = $name;
        }

        return $traitNames;
    }

    /**
     * @return array<trait-string, self>
     */
    public function getTraits(): array
    {
        return iterator_to_array($this->yieldTraits());
    }

    /**
     * @throws TypeAliasDoesNotExist
     */
    public function getTypeAlias(string $name): Type
    {
        return $this->metadata->typeAliases[$name] ?? throw new TypeAliasDoesNotExist($name);
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
        return isset($this->getResolvedConstants()[$name]);
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
            try {
                $interface = $this->reflectClass($interface);
            } catch (ClassDoesNotExist) {
                /** @var string $interface */
                throw new InterfaceDoesNotExist($interface);
            }
        }

        if (!$interface->isInterface()) {
            throw new NotAnInterface($interface->name);
        }

        if ($this->metadata->name === $interface->name) {
            return true;
        }

        foreach ($this->yieldInterfaces() as $implementedInterface) {
            if ($implementedInterface->name === $interface->name) {
                return true;
            }
        }

        return false;
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

    public function isFinal(Origin $origin = Origin::Resolved): bool
    {
        return match ($origin) {
            Origin::PhpDoc => $this->metadata->finalPhpDoc,
            Origin::Native => $this->metadata->finalNative(),
            Origin::Resolved => $this->metadata->finalPhpDoc || $this->metadata->finalNative(),
        };
    }

    public function isInstance(object $object): bool
    {
        return $this->metadata->name === $object::class || $this->reflectClass($object::class)->isSubclassOf($this);
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

    public function isReadonly(Origin $origin = Origin::Resolved): bool
    {
        return match ($origin) {
            Origin::PhpDoc => $this->metadata->readonlyPhpDoc,
            Origin::Native => $this->metadata->readonlyNative(),
            Origin::Resolved => $this->metadata->readonlyPhpDoc || $this->metadata->readonlyNative(),
        };
    }

    public function isSubclassOf(string|\ReflectionClass $class): bool
    {
        if (\is_string($class)) {
            if ($class === $this->metadata->name) {
                return false;
            }

            $class = $this->reflectClass($class);
        } elseif ($class->name === $this->metadata->name) {
            return false;
        }

        if ($class->isInterface() && $this->implementsInterface($class)) {
            return true;
        }

        $parentClass = $this->metadata->parentClass();

        if ($parentClass === null) {
            return false;
        }

        return $class->name === $parentClass || $this->reflectClass($parentClass)->isSubclassOf($class);
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
     * @return array<non-empty-string, ClassConstantReflection>
     */
    private function getResolvedConstants(): array
    {
        return array_map(
            fn(ClassConstantMetadata $metadata): ClassConstantReflection => new ClassConstantReflection(
                classReflector: $this->classReflector,
                metadata: $metadata,
            ),
            $this->metadata->resolvedConstants($this->reflectClassMetadata(...)),
        );
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    private function getResolvedMethods(): array
    {
        return array_map(
            fn(MethodMetadata $metadata): MethodReflection => new MethodReflection(
                classReflector: $this->classReflector,
                metadata: $metadata,
                currentClass: $this->metadata->name,
            ),
            $this->metadata->resolvedMethods($this->reflectClassMetadata(...)),
        );
    }

    /**
     * @return array<non-empty-string, PropertyReflection>
     */
    private function getResolvedProperties(): array
    {
        return array_map(
            fn(PropertyMetadata $metadata): PropertyReflection => new PropertyReflection(
                classReflector: $this->classReflector,
                metadata: $metadata,
            ),
            $this->metadata->resolvedProperties($this->reflectClassMetadata(...)),
        );
    }

    /**
     * @psalm-assert-if-true self $this->getParentClass()
     */
    private function hasParent(): bool
    {
        return $this->metadata->parentClass() !== null;
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            parent::__construct($this->metadata->name);
            $this->nativeLoaded = true;
        }
    }

    /**
     * @param non-empty-string $class
     * @throws ReflectionException
     */
    private function reflectClass(string $class): self
    {
        return $this->classReflector->reflectClass($class);
    }

    /**
     * @param non-empty-string $class
     * @throws ReflectionException
     */
    private function reflectClassMetadata(string $class): ClassMetadata
    {
        return $this->reflectClass($class)->metadata;
    }

    /**
     * @return \Generator<interface-string, self>
     */
    private function yieldInterfaces(): \Generator
    {
        $interfaces = [];
        $ancestors = [];

        foreach ($this->metadata->interfaceClasses() as $interfaceClass) {
            $ancestors[] = $interface = $this->reflectClass($interfaceClass);
            yield $interface->name => $interface;
        }

        foreach ($ancestors as $ancestor) {
            foreach ($ancestor->getInterfaces() as $interface) {
                yield $interface->name => $interface;
            }
        }

        if ($this->hasParent()) {
            foreach ($this->getParentClass()->getInterfaces() as $interface) {
                yield $interface->name => $interface;
            }
        }

        return $interfaces;
    }

    /**
     * @return \Generator<trait-string, self>
     */
    private function yieldTraits(): \Generator
    {
        foreach ($this->metadata->traitClasses() as $traitClass) {
            $trait = $this->reflectClass($traitClass);
            /** @var trait-string */
            $name = $trait->name;
            yield $name => $trait;
        }
    }
}
