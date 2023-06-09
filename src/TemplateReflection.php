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
     * @param int<0, max> $position
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly int $position,
        public readonly string $name,
        public readonly Type $constraint = types::mixed,
        public readonly Variance $variance = Variance::INVARIANT,
    ) {
    }
}
