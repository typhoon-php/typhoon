<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\Encoder;

/**
 * @api
 * @template TObject of object
 * @param class-string<TObject>|array<class-string<TObject>> $classes
 * @param ?non-empty-string $prefix
 * @param callable(TObject): mixed $encoder
 */
function registerObjectEncoder(string|array $classes, callable $encoder, ?string $prefix = null): void
{
    foreach ((array) $classes as $class) {
        Encoder::registerObjectEncoder($class, $prefix ?? $class, $encoder);
    }
}
