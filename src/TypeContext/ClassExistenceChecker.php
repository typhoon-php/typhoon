<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
interface ClassExistenceChecker
{
    /**
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool;
}
