<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\AttributeReflection\AttributeReflections;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Metadata\ClassConstantMetadata;
use Typhoon\Type\Type;

/**
 * @api
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ClassConstantReflection extends \ReflectionClassConstant
{
    private ?AttributeReflections $attributes = null;

    private bool $nativeLoaded = false;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
        private readonly ClassConstantMetadata $metadata,
    ) {
        unset($this->name, $this->class);
    }

    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->metadata->name,
            'class' => $this->metadata->class,
            default => new \OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name)),
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
            $constant = $this->metadata->name;
            $this->attributes = AttributeReflections::create(
                $this->classReflector,
                $this->metadata->attributes,
                static fn(): array => (new \ReflectionClassConstant($class, $constant))->getAttributes(),
            );
        }

        return $this->attributes->get($name, $flags);
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflector->reflectClass($this->metadata->class);
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
        if (!method_exists(parent::class, 'getType')) {
            throw new \BadMethodCallException();
        }

        $this->loadNative();

        /** @var ?\ReflectionType */
        return parent::getType();
    }

    /**
     * @return ($origin is Origin::Resolved ? Type : null|Type)
     */
    public function getTyphoonType(Origin $origin = Origin::Resolved): ?Type
    {
        return $this->metadata->type->get($origin);
    }

    public function getValue(): mixed
    {
        $this->loadNative();

        return parent::getValue();
    }

    public function hasType(): bool
    {
        return $this->metadata->type->native !== null;
    }

    public function isEnumCase(): bool
    {
        return $this->metadata->enumCase;
    }

    public function isFinal(): bool
    {
        return ($this->metadata->modifiers & self::IS_FINAL) !== 0;
    }

    public function isPrivate(): bool
    {
        return ($this->metadata->modifiers & self::IS_PRIVATE) !== 0;
    }

    public function isProtected(): bool
    {
        return ($this->metadata->modifiers & self::IS_PROTECTED) !== 0;
    }

    public function isPublic(): bool
    {
        return ($this->metadata->modifiers & self::IS_PUBLIC) === \ReflectionProperty::IS_PUBLIC;
    }

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            parent::__construct($this->metadata->class, $this->metadata->name);
            $this->nativeLoaded = true;
        }
    }
}
