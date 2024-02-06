<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @template TAttribute of object
 * @psalm-suppress PossiblyUnusedProperty
 */
final class AttributeMetadata
{
    /**
     * @param class-string<TAttribute> $name
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
