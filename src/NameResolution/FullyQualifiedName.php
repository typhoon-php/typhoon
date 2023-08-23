<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class FullyQualifiedName extends Name
{
    public function __construct(
        private readonly UnqualifiedName|QualifiedName $name,
    ) {}

    public function lastSegment(): UnqualifiedName
    {
        return $this->name->lastSegment();
    }

    public function resolveInNamespace(null|UnqualifiedName|QualifiedName $namespace = null): UnqualifiedName|QualifiedName
    {
        return $this->name;
    }

    public function toString(): string
    {
        return '\\' . $this->name->toString();
    }
}
