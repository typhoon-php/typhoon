<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 */
final class AtFunction
{
    /**
     * @param callable-string $function
     */
    public function __construct(
        public readonly string $function,
    ) {
    }
}
