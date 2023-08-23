<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class RelativeName extends Name
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
        if ($namespace === null) {
            return $this->name;
        }

        return self::concatenate($namespace, $this->name);
    }

    public function toString(): string
    {
        return 'namespace\\' . $this->name->toString();
    }
}
