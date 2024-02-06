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
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     * @param list<AttributeMetadata> $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly string|false $docComment,
        public readonly bool $hasDefaultValue,
        public readonly bool $promoted,
        public readonly int $modifiers,
        public readonly bool $deprecated,
        public TypeMetadata $type,
        public readonly ?int $startLine,
        public readonly ?int $endLine,
        public readonly array $attributes,
    ) {}

    public function withType(TypeMetadata $type): self
    {
        $metadata = clone $this;
        $metadata->type = $type;

        return $metadata;
    }
}
