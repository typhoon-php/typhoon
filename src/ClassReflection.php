<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Reflector\ClassReflector;
use Typhoon\Reflection\Reflector\ClassReflectorAwareReflection;
use Typhoon\Reflection\Reflector\RootReflection;
use Typhoon\Reflection\TypeResolver\StaticResolver;
use Typhoon\Reflection\TypeResolver\TemplateResolver;
use Typhoon\Type;

/**
 * @api
 * @template-covariant T of object
 */
final class ClassReflection extends ClassReflectorAwareReflection implements RootReflection
{
    public const IS_IMPLICIT_ABSTRACT = \ReflectionClass::IS_IMPLICIT_ABSTRACT;
    public const IS_EXPLICIT_ABSTRACT = \ReflectionClass::IS_EXPLICIT_ABSTRACT;
    public const IS_FINAL = \ReflectionClass::IS_FINAL;
    public const IS_READONLY = 65536;

    /**
     * @var ?array<non-empty-string, PropertyReflection>
     */
    private ?array $propertiesIndexedByName = null;

    /**
     * @var ?array<non-empty-string, MethodReflection>
     */
    private ?array $methodsIndexedByName = null;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param class-string<T> $name
     * @param ?non-empty-string $extensionName
     * @param ?non-empty-string $file
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     * @param ?non-empty-string $docComment
     * @param list<AttributeReflection> $attributes
     * @param list<TemplateReflection> $templates
     * @param int-mask-of<self::IS_*> $modifiers
     * @param list<Type\NamedObjectType> $ownInterfaceTypes
     * @param list<PropertyReflection> $ownProperties
     * @param list<MethodReflection> $ownMethods
     * @param ?\ReflectionClass<T> $nativeReflection
     */
    public function __construct(
        public readonly string $name,
        private readonly ChangeDetector $changeDetector,
        private readonly bool $internal,
        private readonly ?string $extensionName,
        private readonly ?string $file,
        private readonly ?int $startLine,
        private readonly ?int $endLine,
        private readonly ?string $docComment,
        private readonly array $attributes,
        private readonly array $templates,
        private readonly bool $interface,
        private readonly bool $enum,
        private readonly bool $trait,
        private readonly int $modifiers,
        private readonly bool $anonymous,
        private readonly bool $deprecated,
        private readonly ?Type\NamedObjectType $parentType,
        private readonly array $ownInterfaceTypes,
        private readonly array $ownProperties,
        private readonly array $ownMethods,
        private ?\ReflectionClass $nativeReflection = null,
    ) {}

    /**
     * @return class-string<T>
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-string
     */
    public function getShortName(): string
    {
        $lastSlashPosition = strrpos($this->name, '\\');

        if ($lastSlashPosition === false) {
            return $this->name;
        }

        /** @var non-empty-string */
        return substr($this->name, $lastSlashPosition + 1);
    }

    public function inNamespace(): bool
    {
        return str_contains($this->name, '\\');
    }

    public function getNamespaceName(): string
    {
        $lastSlashPosition = strrpos($this->name, '\\');

        if ($lastSlashPosition === false) {
            return '';
        }

        return substr($this->name, 0, $lastSlashPosition);
    }

    public function getChangeDetector(): ChangeDetector
    {
        return $this->changeDetector;
    }

    /**
     * @return ?non-empty-string
     */
    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function isUserDefined(): bool
    {
        return !$this->internal;
    }

    /**
     * @return ?non-empty-string
     */
    public function getFileName(): ?string
    {
        return $this->file;
    }

    /**
     * @return ?positive-int
     */
    public function getStartLine(): ?int
    {
        return $this->startLine;
    }

    /**
     * @return ?positive-int
     */
    public function getEndLine(): ?int
    {
        return $this->endLine;
    }

    /**
     * @return ?non-empty-string
     */
    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    /**
     * @return list<AttributeReflection>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return list<TemplateReflection>
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    public function hasTemplateWithName(string $name): bool
    {
        foreach ($this->templates as $template) {
            if ($template->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-assert-if-true int<0, max> $position
     */
    public function hasTemplateWithPosition(int $position): bool
    {
        return isset($this->templates[$position]);
    }

    public function getTemplateByPosition(int $position): TemplateReflection
    {
        return $this->templates[$position] ?? throw new ReflectionException();
    }

    public function getTemplateByName(string $name): TemplateReflection
    {
        foreach ($this->templates as $template) {
            if ($template->name === $name) {
                return $template;
            }
        }

        throw new ReflectionException();
    }

    /**
     * @return int-mask-of<self::IS_*>
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    public function isFinal(): bool
    {
        return $this->enum || ($this->modifiers & self::IS_FINAL) !== 0;
    }

    public function isAbstract(): bool
    {
        if ($this->interface) {
            return $this->getMethods() !== [];
        }

        if ($this->trait) {
            foreach ($this->getMethods() as $method) {
                if ($method->isAbstract()) {
                    return true;
                }
            }

            return false;
        }

        return ($this->modifiers & self::IS_EXPLICIT_ABSTRACT) !== 0;
    }

    public function isReadOnly(): bool
    {
        return ($this->modifiers & self::IS_READONLY) !== 0;
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function isInterface(): bool
    {
        return $this->interface;
    }

    public function isEnum(): bool
    {
        return $this->enum;
    }

    public function isTrait(): bool
    {
        return $this->trait;
    }

    public function isCloneable(): bool
    {
        return !$this->isAbstract()
            && !$this->interface
            && !$this->trait
            && !$this->enum
            && (!$this->hasMethod('__clone') || $this->getMethod('__clone')->isPublic());
    }

    public function isInstantiable(): bool
    {
        return !$this->isAbstract()
            && !$this->interface
            && !$this->trait
            && !$this->enum
            && (!$this->hasMethod('__construct') || $this->getMethod('__construct')->isPublic());
    }

    public function isIterable(): bool
    {
        return !$this->interface
            && !$this->isAbstract()
            && $this->implementsInterface(\Traversable::class);
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * @return list<interface-string>
     */
    public function getInterfaceNames(): array
    {
        return array_column($this->getInterfaces(), 'name');
    }

    /**
     * @return list<self>
     */
    public function getInterfaces(): array
    {
        $interfaces = [];
        $ancestors = [];

        foreach ($this->ownInterfaceTypes as $ownInterfaceType) {
            $ownInterface = $this->classReflector()->reflectClass($ownInterfaceType->class);
            $interfaces[$ownInterface->name] = $ownInterface;
            $ancestors[] = $ownInterface;
        }

        $parent = $this->getParentClass();

        if ($parent !== null) {
            $ancestors[] = $parent;
        }

        foreach ($ancestors as $ancestor) {
            foreach ($ancestor->getInterfaces() as $interface) {
                $interfaces[$interface->name] = $interface;
            }
        }

        return array_values($interfaces);
    }

    /**
     * @param interface-string|self $interface
     */
    public function implementsInterface(string|self $interface): bool
    {
        $interface = $this->resolveInterface($interface);

        if ($this->name === $interface->name) {
            return true;
        }

        return \in_array($interface->name, $this->getInterfaceNames(), true);
    }

    /**
     * @return ?class-string
     */
    public function getParentClassName(): ?string
    {
        return $this->parentType?->class;
    }

    public function getParentClass(): ?self
    {
        if ($this->parentType === null) {
            return null;
        }

        return $this->classReflector()->reflectClass($this->parentType->class);
    }

    /**
     * @return list<class-string>
     */
    public function getParentClassNames(): array
    {
        $parent = $this->getParentClass();

        if ($parent === null) {
            return [];
        }

        return [$parent->name, ...$parent->getParentClassNames()];
    }

    /**
     * @return list<self>
     */
    public function getParentClasses(): array
    {
        $parent = $this->getParentClass();

        if ($parent === null) {
            return [];
        }

        return [$parent, ...$parent->getParentClasses()];
    }

    /**
     * @param class-string|self $class
     */
    public function isSubclassOf(string|self $class): bool
    {
        if (\is_string($class)) {
            $class = $this->classReflector()->reflectClass($class);
        }

        if ($class->isInterface() && $this->implementsInterface($class)) {
            return true;
        }

        if ($class->name === $this->name) {
            return true;
        }

        return \in_array($class->name, $this->getParentClassNames(), true);
    }

    public function isInstance(object $object): bool
    {
        return $object instanceof $this->name;
    }

    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    public function hasProperty(string $name): bool
    {
        return isset($this->getPropertiesIndexedByName()[$name]);
    }

    /**
     * @param int-mask-of<PropertyReflection::IS_*> $filter
     * @return list<PropertyReflection>
     */
    public function getProperties(int $filter = 0): array
    {
        if ($filter === 0) {
            return array_values($this->getPropertiesIndexedByName());
        }

        return array_values(array_filter(
            $this->getPropertiesIndexedByName(),
            static fn(PropertyReflection $property): bool => ($filter & $property->getModifiers()) !== 0,
        ));
    }

    /**
     * @return array<non-empty-string, PropertyReflection>
     */
    public function getPropertiesIndexedByName(): array
    {
        if ($this->propertiesIndexedByName !== null) {
            return $this->propertiesIndexedByName;
        }

        $propertiesIndexedByName = array_column($this->ownProperties, null, 'name');

        foreach ($this->parentWithResolvedAncestors()?->getPropertiesIndexedByName() ?? [] as $name => $parentProperty) {
            if ($parentProperty->isPrivate()) {
                continue;
            }

            if (!isset($propertiesIndexedByName[$name])) {
                $propertiesIndexedByName[$name] = $parentProperty;

                continue;
            }

            $propertiesIndexedByName[$name] = PropertyReflection::fromPrototype($parentProperty, $propertiesIndexedByName[$name]);
        }

        return $this->propertiesIndexedByName = $propertiesIndexedByName;
    }

    /**
     * @psalm-assert non-empty-string $name
     */
    public function getProperty(string $name): PropertyReflection
    {
        return $this->getPropertiesIndexedByName()[$name] ?? throw new ReflectionException();
    }

    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    public function hasMethod(string $name): bool
    {
        return isset($this->getMethodsIndexedByName()[$name]);
    }

    /**
     * @param int-mask-of<MethodReflection::IS_*> $filter
     * @return list<MethodReflection>
     */
    public function getMethods(int $filter = 0): array
    {
        if ($filter === 0) {
            return array_values($this->getMethodsIndexedByName());
        }

        return array_values(array_filter(
            $this->getMethodsIndexedByName(),
            static fn(MethodReflection $method): bool => ($filter & $method->getModifiers()) !== 0,
        ));
    }

    /**
     * @return array<non-empty-string, MethodReflection>
     */
    public function getMethodsIndexedByName(): array
    {
        if ($this->methodsIndexedByName !== null) {
            return $this->methodsIndexedByName;
        }

        $methodsIndexedByName = array_column($this->ownMethods, null, 'name');

        foreach ($this->ownAncestorsWithResolvedTemplates() as $ancestor) {
            foreach ($ancestor->getMethodsIndexedByName() as $name => $parentMethod) {
                if ($parentMethod->isPrivate()) {
                    continue;
                }

                if (!isset($methodsIndexedByName[$name])) {
                    $methodsIndexedByName[$name] = $parentMethod;

                    continue;
                }

                $methodsIndexedByName[$name] = MethodReflection::fromPrototype($parentMethod, $methodsIndexedByName[$name]);
            }
        }

        return $this->methodsIndexedByName = $methodsIndexedByName;
    }

    /**
     * @psalm-assert non-empty-string $name
     */
    public function getMethod(string $name): MethodReflection
    {
        return $this->getMethodsIndexedByName()[$name] ?? throw new ReflectionException();
    }

    public function getConstructor(): ?MethodReflection
    {
        return $this->getMethodsIndexedByName()['__construct'] ?? null;
    }

    /**
     * @param array<Type\Type> $templateArguments
     * @return self<T>
     */
    public function resolveTemplates(array $templateArguments = []): self
    {
        if ($this->templates === []) {
            return $this;
        }

        /** @var self<T> */
        return $this->resolvedTypes(TemplateResolver::create($this->templates, $templateArguments));
    }

    /**
     * @deprecated in favor of resolveTemplates()
     * @param array<Type\Type> $templateArguments
     * @return self<T>
     */
    public function withResolvedTemplates(array $templateArguments = []): self
    {
        return $this->resolveTemplates($templateArguments);
    }

    /**
     * @return self<T>
     */
    public function resolveStatic(): self
    {
        /** @var self<T> */
        return $this->resolvedTypes(new StaticResolver($this->name));
    }

    /**
     * @deprecated in favor of resolveStatic()
     * @return self<T>
     */
    public function withResolvedStatic(): self
    {
        return $this->resolveStatic();
    }

    /**
     * @return T
     */
    public function newInstance(mixed ...$args): object
    {
        return $this->getNativeReflection()->newInstance(...$args);
    }

    /**
     * @return T
     */
    public function newInstanceArgs(array $args = []): object
    {
        return $this->getNativeReflection()->newInstanceArgs($args);
    }

    /**
     * @return T
     */
    public function newInstanceWithoutConstructor(): object
    {
        return $this->getNativeReflection()->newInstanceWithoutConstructor();
    }

    public function __serialize(): array
    {
        return array_diff_key(get_object_vars($this), [
            'propertiesIndexedByName' => null,
            'methodsIndexedByName' => null,
            'nativeReflection' => null,
        ]);
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
    }

    public function __clone()
    {
        if ((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null) !== self::class) {
            throw new ReflectionException();
        }
    }

    /**
     * @return \ReflectionClass<T>
     */
    public function getNativeReflection(): \ReflectionClass
    {
        return $this->nativeReflection ??= new \ReflectionClass($this->name);
    }

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function setClassReflector(ClassReflector $classReflector): void
    {
        parent::setClassReflector($classReflector);

        foreach ([...$this->ownProperties, ...$this->ownMethods] as $reflection) {
            $reflection->setClassReflector($classReflector);
        }
    }

    private function resolvedTypes(TemplateResolver|StaticResolver $typeResolver): self
    {
        $class = clone $this;
        $class->propertiesIndexedByName = array_map(
            static fn(PropertyReflection $property): PropertyReflection => $property->resolveTypes($typeResolver),
            $this->getPropertiesIndexedByName(),
        );
        $class->methodsIndexedByName = array_map(
            static fn(MethodReflection $method): MethodReflection => $method->resolveTypes($typeResolver),
            $this->getMethodsIndexedByName(),
        );

        return $class;
    }

    /**
     * @param interface-string|self $interface
     */
    private function resolveInterface(string|self $interface): self
    {
        if (\is_string($interface)) {
            $interface = $this->classReflector()->reflectClass($interface);
        }

        if (!$interface->isInterface()) {
            throw new ReflectionException();
        }

        return $interface;
    }

    private function parentWithResolvedAncestors(): ?self
    {
        if ($this->parentType === null) {
            return null;
        }

        return $this->classReflector()
            ->reflectClass($this->parentType->class)
            ->resolveTemplates($this->parentType->templateArguments);
    }

    /**
     * @return \Generator<self>
     */
    private function ownAncestorsWithResolvedTemplates(): \Generator
    {
        $parentWithResolvedAncestors = $this->parentWithResolvedAncestors();

        if ($parentWithResolvedAncestors !== null) {
            yield $parentWithResolvedAncestors;
        }

        foreach ($this->ownInterfaceTypes as $interfaceType) {
            yield $this->classReflector()
                ->reflectClass($interfaceType->class)
                ->resolveTemplates($interfaceType->templateArguments);
        }
    }
}
