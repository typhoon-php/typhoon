<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class RelativeName
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

    public function resolve(null|UnqualifiedName|QualifiedName $namespace = null): UnqualifiedName|QualifiedName
    {
        return $this->name->resolve($namespace);
    }
}
