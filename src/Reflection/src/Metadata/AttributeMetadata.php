<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class AttributeMetadata
{
    /**
     * @param non-empty-string $name
     * @param non-negative-int $position
     * @param \Attribute::TARGET_* $target
     */
    public function __construct(
        public readonly string $name,
        public readonly int $position,
        public readonly int $target,
        public readonly bool $repeated,
    ) {}
}
