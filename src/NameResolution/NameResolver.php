<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant TReturn
 * @template TTemplateMetadata
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
     * @param class-string $class
     * @param non-empty-string $name
     * @param TTemplateMetadata $metadata
     * @return TReturn
     */
    public function classTemplate(string $class, string $name, mixed $metadata): mixed;

    /**
     * @param class-string $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     * @param TTemplateMetadata $metadata
     * @return TReturn
     */
    public function methodTemplate(string $class, string $method, string $name, mixed $metadata): mixed;

    /**
     * @param non-empty-string $classCandidate
     * @param non-empty-list<non-empty-string> $constantCandidates
     * @return TReturn
     */
    public function classOrConstants(string $classCandidate, array $constantCandidates): mixed;
}
