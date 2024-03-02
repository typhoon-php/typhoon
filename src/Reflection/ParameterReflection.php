<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\AttributeReflection\AttributeReflections;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\TypeReflection\TypeConverter;
use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Type;

/**
 * @api
 * @property-read non-empty-string $name
 * @psalm-suppress PropertyNotSetInConstructor
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

    public function allowsNull(): bool
    {
        return $this->metadata->type->native?->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function null(Type $self): mixed
                {
                    return true;
                }

                public function union(Type $self, array $types): mixed
                {
                    foreach ($types as $type) {
                        if ($type->accept($this)) {
                            return true;
                        }
                    }

                    return false;
                }

                public function mixed(Type $self): mixed
                {
                    return true;
                }

                protected function default(Type $self): mixed
                {
                    return false;
                }
            },
        ) ?? true;
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
            $this->attributes = AttributeReflections::create(
                $this->classReflector,
                $this->metadata->attributes,
                static fn(): array => (new \ReflectionParameter($function, $parameter))->getAttributes(),
            );
        }

        return $this->attributes->get($name, $flags);
    }

    public function getClass(): ?ClassReflection
    {
        return $this->metadata->type->native?->accept(
            new /** @extends DefaultTypeVisitor<?ClassReflection> */ class ($this->classReflector) extends DefaultTypeVisitor {
                public function __construct(
                    private readonly ClassReflector $classReflector,
                ) {}

                public function namedObject(Type $self, string $class, array $arguments): mixed
                {
                    /** @psalm-suppress InternalMethod */
                    return $this->classReflector->reflectClass($class);
                }

                public function closure(Type $self, array $parameters, Type $return): mixed
                {
                    /** @psalm-suppress InternalMethod */
                    return $this->classReflector->reflectClass(\Closure::class);
                }

                protected function default(Type $self): mixed
                {
                    return null;
                }
            },
        );
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
        return $this->getDeclaringClass()?->getMethod($this->metadata->functionOrMethod)
            ?? throw new \LogicException('Functions are not supported yet');
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
        return $this->metadata->type->native?->accept(new TypeConverter());
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
        return $this->metadata->type->native?->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function array(Type $self, Type $key, Type $value, array $elements): mixed
                {
                    return true;
                }

                protected function default(Type $self): mixed
                {
                    return false;
                }
            },
        ) ?? false;
    }

    public function isCallable(): bool
    {
        return $this->metadata->type->native?->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function callable(Type $self, array $parameters, Type $return): mixed
                {
                    return true;
                }

                protected function default(Type $self): mixed
                {
                    return false;
                }
            },
        ) ?? false;
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
