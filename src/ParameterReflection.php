<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\ClassReflection\ClassReflectorAwareReflection;

/**
 * @api
 */
final class ParameterReflection extends ClassReflectorAwareReflection
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param ?class-string $class
     * @param non-empty-string $functionOrMethod
     * @param int<0, max> $position
     * @param non-empty-string $name
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     */
    public function __construct(
        private readonly int $position,
        public readonly string $name,
        private readonly ?string $class,
        private readonly string $functionOrMethod,
        private readonly bool $passedByReference,
        private readonly bool $defaultValueAvailable,
        private readonly bool $optional,
        private readonly bool $variadic,
        private readonly bool $promoted,
        private readonly bool $deprecated,
        /** @readonly */
        private TypeReflection $type,
        private readonly ?int $startLine,
        private readonly ?int $endLine,
        private ?\ReflectionParameter $nativeReflection = null,
    ) {}

    public function getDeclaringClass(): ?ClassReflection
    {
        if ($this->class === null) {
            return null;
        }

        return $this->classReflector()->reflectClass($this->class);
    }

    public function getDeclaringFunction(): MethodReflection
    {
        if ($this->class === null) {
            throw new ReflectionException();
        }

        return $this->classReflector()->reflectClass($this->class)->getMethod($this->functionOrMethod);
    }

    public function canBePassedByValue(): bool
    {
        return !$this->passedByReference;
    }

    public function getDefaultValue(): mixed
    {
        return $this->getNativeReflection()->getDefaultValue();
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int<0, max>
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    public function getType(): TypeReflection
    {
        return $this->type;
    }

    public function isDefaultValueAvailable(): bool
    {
        return $this->defaultValueAvailable;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function isPassedByReference(): bool
    {
        return $this->passedByReference;
    }

    public function isPromoted(): bool
    {
        return $this->promoted;
    }

    public function isVariadic(): bool
    {
        return $this->variadic;
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
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

    public function getNativeReflection(): \ReflectionParameter
    {
        return $this->nativeReflection ??= new \ReflectionParameter(
            $this->class === null ? $this->functionOrMethod : [$this->class, $this->functionOrMethod],
            $this->name,
        );
    }

    public function __serialize(): array
    {
        return array_diff_key(get_object_vars($this), ['nativeReflection' => null]);
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
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function withType(TypeReflection $type): self
    {
        $property = clone $this;
        $property->type = $type;

        return $property;
    }
}
