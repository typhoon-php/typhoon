<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeContext;

interface ConstantExistenceChecker
{
    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    public function constantExists(string $name): bool;
}
