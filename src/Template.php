<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Type\MixedT;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class Template
{
    /**
     * @param int<0, max> $index
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly int $index,
        public readonly string $name,
        public readonly Type $constraint = new MixedT(),
        public readonly Variance $variance = Variance::INVARIANT,
    ) {
    }
}
