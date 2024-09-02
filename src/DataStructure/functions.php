<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\Encoder;

/**
 * @api
 * @template TObject of object
 * @param non-empty-list<class-string<TObject>> $classes
 * @param ?non-empty-string $prefix
 * @param callable(TObject): mixed $encoder
 */
function registerObjectEncoder(array $classes, callable $encoder, ?string $prefix = null): void
{
    foreach ($classes as $class) {
        Encoder::registerObjectEncoder($class, $prefix ?? $class, $encoder);
    }
}
