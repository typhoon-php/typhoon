<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Attributes\AttributeReflections;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\Nullability\NullableChecker;
use Typhoon\Type\ArrayType;
use Typhoon\Type\CallableType;
use Typhoon\Type\ClosureType;
use Typhoon\Type\NamedObjectType;
use Typhoon\Type\Type;

/**
 * @api
 * @property-read non-empty-string $name
 * @psalm-suppress PropertyNotSetInConstructor, MissingImmutableAnnotation
 */
final class ParameterReflection extends \ReflectionParameter
{
    private ?AttributeReflections $attributes = null;

    private bool $nativeLoaded = false;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
        private readonly ParameterMetadata $metadata,
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

    public function allowsNull(): bool
    {
        return NullableChecker::isNullable($this->metadata->type->native);
    }

    public function canBePassedByValue(): bool
    {
        return !$this->metadata->passedByReference;
    }

    /**
     * @template TClass as object
     * @param class-string<TClass>|null $name
     * @return ($name is null ? list<AttributeReflection<object>> : list<AttributeReflection<TClass>>)
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($this->attributes === null) {
            $function = $this->function();
            $parameter = $this->metadata->name;
            $this->attributes = new AttributeReflections(
                $this->classReflector,
                $this->metadata->attributes,
                static fn(): array => (new \ReflectionParameter($function, $parameter))->getAttributes(),
            );
        }

        return $this->attributes->get($name, $flags);
    }

    public function getClass(): ?\ReflectionClass
    {
        $nativeType = $this->metadata->type->native;

        if ($nativeType instanceof NamedObjectType) {
            return $this->classReflector->reflectClass($nativeType->class);
        }

        if ($nativeType instanceof ClosureType) {
            return $this->classReflector->reflectClass(\Closure::class);
        }

        return null;
    }

    public function getDeclaringClass(): ?ClassReflection
    {
        if ($this->metadata->class === null) {
            return null;
        }

        return $this->classReflector->reflectClass($this->metadata->class);
    }

    public function getDeclaringFunction(): MethodReflection
    {
        return $this->getDeclaringClass()?->getMethod($this->metadata->functionOrMethod) ?? throw new ReflectionException();
    }

    public function getDefaultValue(): mixed
    {
        $this->loadNative();

        return parent::getDefaultValue();
    }

    public function getDefaultValueConstantName(): ?string
    {
        $this->loadNative();

        return parent::getDefaultValueConstantName();
    }

    /**
     * @return positive-int|false
     */
    public function getEndLine(): int|false
    {
        return $this->metadata->endLine;
    }

    public function getName(): string
    {
        return $this->metadata->name;
    }

    public function getPosition(): int
    {
        return $this->metadata->position;
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
        $this->loadNative();

        return parent::getType();
    }

    /**
     * @return ($origin is Origin::Resolved ? Type : null|Type)
     */
    public function getTyphoonType(Origin $origin = Origin::Resolved): ?Type
    {
        return $this->metadata->type->get($origin);
    }

    public function hasType(): bool
    {
        return $this->metadata->type->native !== null;
    }

    public function isArray(): bool
    {
        return $this->metadata->type->native instanceof ArrayType;
    }

    public function isCallable(): bool
    {
        return $this->metadata->type->native instanceof CallableType;
    }

    public function isDefaultValueAvailable(): bool
    {
        return $this->metadata->defaultValueAvailable;
    }

    public function isDefaultValueConstant(): bool
    {
        $this->loadNative();

        return parent::isDefaultValueConstant();
    }

    public function isDeprecated(): bool
    {
        return $this->metadata->deprecated;
    }

    public function isOptional(): bool
    {
        return $this->metadata->optional;
    }

    public function isPassedByReference(): bool
    {
        return $this->metadata->passedByReference;
    }

    public function isPromoted(): bool
    {
        return $this->metadata->promoted;
    }

    public function isVariadic(): bool
    {
        return $this->metadata->variadic;
    }

    /**
     * @return non-empty-string|array{class-string, non-empty-string}
     */
    private function function(): array|string
    {
        if ($this->metadata->class === null) {
            return $this->metadata->functionOrMethod;
        }

        return [$this->metadata->class, $this->metadata->functionOrMethod];
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            parent::__construct($this->function(), $this->metadata->name);
            $this->nativeLoaded = true;
        }
    }
}
