<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Reflector\FriendlyReflection;
use Typhoon\TypeVisitor;

/**
 * @api
 */
final class PropertyReflection extends FriendlyReflection
{
    public const IS_PUBLIC = \ReflectionProperty::IS_PUBLIC;
    public const IS_PROTECTED = \ReflectionProperty::IS_PROTECTED;
    public const IS_PRIVATE = \ReflectionProperty::IS_PRIVATE;
    public const IS_STATIC = \ReflectionProperty::IS_STATIC;
    public const IS_READONLY = \ReflectionProperty::IS_READONLY;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param non-empty-string $name
     * @param ?non-empty-string $docComment
     * @param int-mask-of<self::IS_*> $modifiers
     * @param class-string $class
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     */
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        private readonly ?string $docComment,
        private readonly bool $hasDefaultValue,
        private readonly bool $promoted,
        private readonly int $modifiers,
        private readonly TypeReflection $type,
        private readonly ?int $startLine,
        private readonly ?int $endLine,
        private ?\ReflectionProperty $reflectionProperty = null,
    ) {}

    public function getDefaultValue(): mixed
    {
        return $this->reflectionProperty()->getDefaultValue();
    }

    /**
     * @return ?non-empty-string
     */
    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): TypeReflection
    {
        return $this->type;
    }

    public function getValue(?object $object = null): mixed
    {
        return $this->reflectionProperty()->getValue($object);
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    public function isInitialized(?object $object = null): bool
    {
        /** @var bool */
        return $this->reflectionProperty()->isInitialized($object);
    }

    /**
     * @return int-mask-of<self::IS_*>
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    public function isStatic(): bool
    {
        return ($this->modifiers & self::IS_STATIC) !== 0;
    }

    public function isPublic(): bool
    {
        return ($this->modifiers & self::IS_PUBLIC) === \ReflectionProperty::IS_PUBLIC;
    }

    public function isProtected(): bool
    {
        return ($this->modifiers & self::IS_PROTECTED) !== 0;
    }

    public function isPrivate(): bool
    {
        return ($this->modifiers & self::IS_PRIVATE) !== 0;
    }

    public function isReadOnly(): bool
    {
        return ($this->modifiers & self::IS_READONLY) !== 0;
    }

    public function isPromoted(): bool
    {
        return $this->promoted;
    }

    public function setValue(?object $object, mixed $value): void
    {
        if ($this->isStatic()) {
            $this->reflectionProperty()->setValue($value);
        } else {
            $this->reflectionProperty()->setValue($object, $value);
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

    public function __serialize(): array
    {
        $data = get_object_vars($this);
        unset($data['reflectionProperty']);

        return $data;
    }

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        $vars = get_object_vars($this);
        $vars['type'] = $this->type->withResolvedTypes($typeResolver);
        $vars['modifiers'] = $this->modifiers;

        return new self(...$vars);
    }

    protected function toChildOf(FriendlyReflection $parent): static
    {
        $data = get_object_vars($this);
        $data['type'] = $this->type->toChildOf($parent->type);
        $data['modifiers'] = $this->modifiers;

        return new self(...$data);
    }

    private function reflectionProperty(): \ReflectionProperty
    {
        return $this->reflectionProperty ??= new \ReflectionProperty($this->class, $this->name);
    }

    private function __clone() {}
}
