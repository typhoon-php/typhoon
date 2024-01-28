<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template T
 */
interface NameResolver
{
    /**
     * @param class-string $name
     * @return T
     */
    public function class(string $name): mixed;

    /**
     * @param class-string $self
     * @return T
     */
    public function static(string $self): mixed;

    /**
     * @param non-empty-string $name
     * @return T
     */
    public function constant(string $name): mixed;

    /**
     * @param class-string $class
     * @param non-empty-string $name
     * @return T
     */
    public function classTemplate(string $class, string $name): mixed;

    /**
     * @param class-string $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     * @return T
     */
    public function methodTemplate(string $class, string $method, string $name): mixed;

    /**
     * @param non-empty-string $classCandidate
     * @param non-empty-list<non-empty-string> $constantCandidates
     * @return T
     */
    public function classOrConstants(string $classCandidate, array $constantCandidates): mixed;
}
