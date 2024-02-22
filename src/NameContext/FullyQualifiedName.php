<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class FullyQualifiedName
{
    public function __construct(
        private readonly UnqualifiedName|QualifiedName $name,
    ) {}

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function lastSegment(): UnqualifiedName
    {
        return $this->name->lastSegment();
    }

    public function resolve(): UnqualifiedName|QualifiedName
    {
        return $this->name;
    }
}
