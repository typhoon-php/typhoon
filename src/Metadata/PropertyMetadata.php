<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class PropertyMetadata
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param non-empty-string $name
     * @param class-string $class
     * @param non-empty-string|false $docComment
     * @param int-mask-of<\ReflectionProperty::IS_*> $modifiers
     * @param positive-int|false $startLine
     * @param positive-int|false $endLine
     * @param list<AttributeMetadata> $attributes
     */
    public function __construct(
        public readonly string $name,
        public string $class,
        public readonly int $modifiers,
        public TypeMetadata $type,
        public readonly string|false $docComment = false,
        public readonly bool $hasDefaultValue = false,
        public readonly bool $promoted = false,
        public readonly bool $deprecated = false,
        public readonly int|false $startLine = false,
        public readonly int|false $endLine = false,
        public readonly array $attributes = [],
    ) {}

    /**
     * @param class-string $class
     */
    public function withClass(string $class): self
    {
        $metadata = clone $this;
        $metadata->class = $class;

        return $metadata;
    }

    public function withType(TypeMetadata $type): self
    {
        $metadata = clone $this;
        $metadata->type = $type;

        return $metadata;
    }
}
