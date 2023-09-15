<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
interface ReflectionContext
{
    /**
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool;

    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    public function reflectClass(string $name): ClassReflection;
}
