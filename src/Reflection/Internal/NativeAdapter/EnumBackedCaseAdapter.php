<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\Reflection\ClassConstReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class EnumBackedCaseAdapter extends \ReflectionEnumBackedCase
{
    public function __construct(
        private readonly ClassConstantAdapter $constant,
        private readonly ClassConstReflection $reflection,
    ) {
        unset($this->name, $this->class);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name)
    {
        return $this->constant->__get($name);
    }

    public function __isset(string $name): bool
    {
        return $this->constant->__isset($name);
    }

    public function __toString(): string
    {
        return $this->constant->__toString();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return $this->constant->getAttributes($name, $flags);
    }

    public function getBackingValue(): int|string
    {
        return $this->reflection->enumBackingValue() ?? throw new \ReflectionException('Not a backed enum');
    }

    public function getDeclaringClass(): \ReflectionClass
    {
        return $this->constant->getDeclaringClass();
    }

    public function getDocComment(): string|false
    {
        return $this->constant->getDocComment();
    }

    public function getEnum(): \ReflectionEnum
    {
        $enum = $this->constant->getDeclaringClass();
        \assert($enum instanceof \ReflectionEnum);

        return $enum;
    }

    public function getModifiers(): int
    {
        return $this->constant->getModifiers();
    }

    public function getName(): string
    {
        return $this->constant->name;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, UnusedPsalmSuppress
     */
    public function getType(): ?\ReflectionType
    {
        return $this->constant->getType();
    }

    public function getValue(): \UnitEnum
    {
        $value = $this->constant->getValue();
        \assert($value instanceof \UnitEnum);

        return $value;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, UnusedPsalmSuppress
     */
    public function hasType(): bool
    {
        return $this->constant->hasType();
    }

    public function isEnumCase(): bool
    {
        return $this->constant->isEnumCase();
    }

    public function isFinal(): bool
    {
        return $this->constant->isFinal();
    }

    public function isPrivate(): bool
    {
        return $this->constant->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->constant->isProtected();
    }

    public function isPublic(): bool
    {
        return $this->constant->isPublic();
    }
}
