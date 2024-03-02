<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\AttributeReflection\AttributeReflections;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\TypeReflection\TypeConverter;
use Typhoon\Type\Type;

/**
 * @api
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PropertyReflection extends \ReflectionProperty
{
    private ?AttributeReflections $attributes = null;

    private bool $nativeLoaded = false;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
        private readonly PropertyMetadata $metadata,
    ) {
        unset($this->name, $this->class);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->metadata->name,
            'class' => $this->metadata->class,
            default => new \LogicException(sprintf('Undefined property %s::$%s', self::class, $name)),
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'name' || $name === 'class';
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
            $class = $this->metadata->class;
            $property = $this->metadata->name;
            $this->attributes = AttributeReflections::create(
                $this->classReflector,
                $this->metadata->attributes,
                static fn(): array => (new \ReflectionProperty($class, $property))->getAttributes(),
            );
        }

        return $this->attributes->get($name, $flags);
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflector->reflectClass($this->metadata->class);
    }

    public function getDefaultValue(): mixed
    {
        $this->loadNative();

        return parent::getDefaultValue();
    }

    public function getDocComment(): string|false
    {
        return $this->metadata->docComment;
    }

    /**
     * @return positive-int|false
     */
    public function getEndLine(): int|false
    {
        return $this->metadata->endLine;
    }

    public function getModifiers(): int
    {
        return $this->metadata->modifiers;
    }

    public function getName(): string
    {
        return $this->metadata->name;
    }

    /**
     * @return positive-int|false
     */
    public function getStartLine(): int|false
    {
        return $this->metadata->startLine;
    }

    public function getType(): ?\ReflectionType
    {
        return $this->metadata->type->native?->accept(new TypeConverter());
    }

    /**
     * @return ($origin is Origin::Resolved ? Type : null|Type)
     */
    public function getTyphoonType(Origin $origin = Origin::Resolved): ?Type
    {
        return $this->metadata->type->get($origin);
    }

    public function getValue(?object $object = null): mixed
    {
        $this->loadNative();

        return parent::getValue($object);
    }

    public function hasDefaultValue(): bool
    {
        return $this->metadata->hasDefaultValue;
    }

    public function hasType(): bool
    {
        return $this->metadata->type->native !== null;
    }

    public function isDefault(): bool
    {
        return true;
    }

    public function isDeprecated(): bool
    {
        return $this->metadata->deprecated;
    }

    public function isInitialized(?object $object = null): bool
    {
        $this->loadNative();

        return parent::isInitialized($object);
    }

    public function isPrivate(): bool
    {
        return ($this->metadata->modifiers & self::IS_PRIVATE) !== 0;
    }

    public function isPromoted(): bool
    {
        return $this->metadata->promoted;
    }

    public function isProtected(): bool
    {
        return ($this->metadata->modifiers & self::IS_PROTECTED) !== 0;
    }

    public function isPublic(): bool
    {
        return ($this->metadata->modifiers & self::IS_PUBLIC) === \ReflectionProperty::IS_PUBLIC;
    }

    public function isReadonly(Origin $origin = Origin::Resolved): bool
    {
        return match ($origin) {
            Origin::PhpDoc => $this->metadata->readonlyPhpDoc,
            Origin::Native => $this->metadata->readonlyNative(),
            Origin::Resolved => $this->metadata->readonlyPhpDoc || $this->metadata->readonlyNative(),
        };
    }

    public function isStatic(): bool
    {
        return ($this->metadata->modifiers & self::IS_STATIC) !== 0;
    }

    public function setAccessible(bool $accessible): void {}

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function setValue(mixed $objectOrValue, mixed $value = null): void
    {
        $this->loadNative();

        if (\func_num_args() === 1) {
            parent::setValue($objectOrValue);
        }

        \assert(\is_object($objectOrValue));

        parent::setValue($objectOrValue, $value);
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            parent::__construct($this->metadata->class, $this->metadata->name);
            $this->nativeLoaded = true;
        }
    }
}
