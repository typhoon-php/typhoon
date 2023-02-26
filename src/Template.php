<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Reflection;

use PHP\ExtendedTypeSystem\Type\MixedT;
use PHP\ExtendedTypeSystem\Type\Type;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class Template
{
    public function __construct(
        public readonly string $name,
        public readonly Type $constraint = new MixedT(),
        public readonly Variance $variance = Variance::INVARIANT,
    ) {
    }
}
