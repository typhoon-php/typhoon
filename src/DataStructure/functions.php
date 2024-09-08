<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\UniqueHasher;

/**
 * @api
 * @template TObject of object
 * @param class-string<TObject>|array<class-string<TObject>> $classes
 * @param ?non-empty-string $prefix
 * @param callable(TObject): mixed $hasher
 */
function registerObjectHasher(string|array $classes, callable $hasher, ?string $prefix = null): void
{
    foreach ((array) $classes as $class) {
        UniqueHasher::registerObjectHasher($class, $prefix ?? $class, $hasher);
    }
}
