<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\TypeContext
 */
final class RuntimeExistenceChecker implements ConstantExistenceChecker, ClassExistenceChecker
{
    public function constantExists(string $name): bool
    {
        return \defined($name);
    }

    public function classExists(string $name): bool
    {
        return class_exists($name) || interface_exists($name);
    }
}
