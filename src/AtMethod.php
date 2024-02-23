<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
final class AtMethod
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $class,
        public readonly string $name,
    ) {}
}
