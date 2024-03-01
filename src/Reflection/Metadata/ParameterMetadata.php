<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class ParameterMetadata
{
    /**
     * @param ?class-string $class
     * @param non-empty-string $functionOrMethod
     * @param non-negative-int $position
     * @param non-empty-string $name
     * @param positive-int|false $startLine
     * @param positive-int|false $endLine
     * @param list<AttributeMetadata> $attributes
     */
    public function __construct(
        public readonly int $position,
        public readonly string $name,
        public readonly ?string $class,
        public readonly string $functionOrMethod,
        public TypeMetadata $type,
        public readonly bool $passedByReference = false,
        public readonly bool $defaultValueAvailable = false,
        public readonly bool $optional = false,
        public readonly bool $variadic = false,
        public readonly bool $promoted = false,
        public readonly bool $deprecated = false,
        public readonly int|false $startLine = false,
        public readonly int|false $endLine = false,
        public readonly array $attributes = [],
    ) {}

    public function withType(TypeMetadata $type): self
    {
        $metadata = clone $this;
        $metadata->type = $type;

        return $metadata;
    }
}
