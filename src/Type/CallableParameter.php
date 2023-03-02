<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TType
 */
final class CallableParameter
{
    /**
     * @param Type<TType> $type
     */
    public function __construct(
        public readonly Type $type = new MixedT(),
        public readonly bool $hasDefault = false,
        public readonly bool $variadic = false,
    ) {
    }
}
