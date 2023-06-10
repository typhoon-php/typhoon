<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 * @template T
 */
interface NameResolver
{
    /**
     * @param non-empty-string $name
     * @return T
     */
    public function class(string $name): mixed;

    /**
     * @param non-empty-string $class
     * @return T
     */
    public function static(string $class): mixed;

    /**
     * @param non-empty-string $name
     * @return T
     */
    public function constant(string $name): mixed;

    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     * @return T
     */
    public function classTemplate(string $class, string $name): mixed;

    /**
     * @param non-empty-string $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     * @return T
     */
    public function methodTemplate(string $class, string $method, string $name): mixed;

    /**
     * @param non-empty-string $class
     * @param non-empty-list<non-empty-string> $constants
     * @return T
     */
    public function classOrConstants(string $class, array $constants): mixed;
}
