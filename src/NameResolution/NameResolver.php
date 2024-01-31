<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant TReturn
 */
interface NameResolver
{
    /**
     * @param class-string $name
     * @return TReturn
     */
    public function class(string $name): mixed;

    /**
     * @param class-string $self
     * @return TReturn
     */
    public function static(string $self): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function constant(string $name): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function template(string $name): mixed;

    /**
     * @param non-empty-string $classCandidate
     * @param non-empty-list<non-empty-string> $constantCandidates
     * @return TReturn
     */
    public function classOrConstants(string $classCandidate, array $constantCandidates): mixed;
}
