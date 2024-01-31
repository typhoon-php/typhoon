<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

final class NativeClassExistenceChecker implements ClassExistenceChecker
{
    public function classExists(string $name): bool
    {
        return class_exists($name) || interface_exists($name);
    }
}
