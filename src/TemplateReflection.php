<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class TemplateReflection
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param int<0, max> $index
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly int $index,
        public readonly string $name,
        public readonly Type $constraint = types::mixed,
        public readonly Variance $variance = Variance::INVARIANT,
    ) {
    }
}
