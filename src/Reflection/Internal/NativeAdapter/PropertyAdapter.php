<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\ModifierKind;
use Typhoon\Reflection\PropertyReflection;
use Typhoon\Reflection\TypeKind;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PropertyAdapter extends \ReflectionProperty
{
    public function __construct(
        private readonly PropertyReflection $reflection,
        private readonly TyphoonReflector $reflector,
    ) {
        unset($this->name, $this->class);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->reflection->id->name,
            'class' => $this->getDeclaringClass()->name,
            default => new \LogicException(\sprintf('Undefined property %s::$%s', self::class, $name)),
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

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::fromList($this->reflection->attributes(), $name, $flags);
    }

    public function getDeclaringClass(): \ReflectionClass
    {
        $declaringClassId = $this->reflection->data[Data::DeclaringClassId];

        if ($declaringClassId instanceof AnonymousClassId) {
            return $this->reflector->reflect($this->reflection->id->class)->toNativeReflection();
        }

        $declaringClass = $this->reflector->reflect($declaringClassId);

        if ($declaringClass->isTrait()) {
            return $this->reflection->class()->toNativeReflection();
        }

        return $declaringClass->toNativeReflection();
    }

    public function getDefaultValue(): mixed
    {
        return $this->reflection->evaluateDefault();
    }

    public function getDocComment(): string|false
    {
        return $this->reflection->phpDoc() ?? false;
    }

    public function getModifiers(): int
    {
        return ($this->isStatic() ? self::IS_STATIC : 0)
            | ($this->isPublic() ? self::IS_PUBLIC : 0)
            | ($this->isProtected() ? self::IS_PROTECTED : 0)
            | ($this->isPrivate() ? self::IS_PRIVATE : 0)
            | ($this->isReadonly() ? self::IS_READONLY : 0);
    }

    public function getName(): string
    {
        return $this->reflection->id->name;
    }

    public function getType(): ?\ReflectionType
    {
        return $this->reflection->type(TypeKind::Native)?->accept(new ToNativeTypeConverter());
    }

    public function getValue(?object $object = null): mixed
    {
        $this->loadNative();

        return parent::getValue($object);
    }

    public function hasDefaultValue(): bool
    {
        return $this->reflection->hasDefaultValue();
    }

    public function hasType(): bool
    {
        return $this->reflection->type(TypeKind::Native) !== null;
    }

    public function isDefault(): bool
    {
        return true;
    }

    public function isInitialized(?object $object = null): bool
    {
        $this->loadNative();

        return parent::isInitialized($object);
    }

    public function isPrivate(): bool
    {
        return $this->reflection->isPrivate();
    }

    public function isPromoted(): bool
    {
        return $this->reflection->isPromoted();
    }

    public function isProtected(): bool
    {
        return $this->reflection->isProtected();
    }

    public function isPublic(): bool
    {
        return $this->reflection->isPublic();
    }

    public function isReadonly(): bool
    {
        return $this->reflection->isReadonly(ModifierKind::Native);
    }

    public function isStatic(): bool
    {
        return $this->reflection->isStatic();
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

    private bool $nativeLoaded = false;

    private function loadNative(): void
    {
        if ($this->nativeLoaded) {
            return;
        }

        $class = $this->reflection->id->class->name ?? throw new \LogicException(\sprintf(
            "Cannot natively reflect %s, because it's runtime name is not available",
            $this->reflection->id->class->describe(),
        ));

        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct($class, $this->name);
        $this->nativeLoaded = true;
    }
}
