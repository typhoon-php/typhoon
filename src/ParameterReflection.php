<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Reflector\ClassReflector;
use Typhoon\Reflection\Reflector\ContextAwareReflection;
use Typhoon\Reflection\TypeResolver\StaticResolver;
use Typhoon\Reflection\TypeResolver\TemplateResolver;

/**
 * @api
 */
final class ParameterReflection extends ContextAwareReflection
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private readonly ClassReflector $classReflector;

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

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public static function fromPrototype(self $prototype, self $child): self
    {
        $new = clone $child;
        $new->type = TypeReflection::fromPrototype($prototype->type, $child->type);

        return $new;
    }

    public function getDeclaringClass(): ?ClassReflection
    {
        if ($this->class === null) {
            return null;
        }

        return $this->classReflector->reflectClass($this->class);
    }

    public function getDeclaringFunction(): MethodReflection
    {
        if ($this->class === null) {
            throw new ReflectionException();
        }

        return $this->classReflector->reflectClass($this->class)->getMethod($this->functionOrMethod);
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

    public function __serialize(): array
    {
        return array_diff_key(get_object_vars($this), [
            'classReflector' => null,
            'nativeReflection' => null,
        ]);
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
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

    public function resolveTypes(TemplateResolver|StaticResolver $typeResolver): self
    {
        $parameter = clone $this;
        $parameter->type = $this->type->resolve($typeResolver);

        return $parameter;
    }

    protected function setClassReflector(ClassReflector $classReflector): void
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->classReflector = $classReflector;
    }
}
