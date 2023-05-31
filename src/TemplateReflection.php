<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;

/**
 * @api
 * @psalm-immutable
 */
final class TemplateReflection
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem\Reflection
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
