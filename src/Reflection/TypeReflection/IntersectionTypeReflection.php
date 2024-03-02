<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class IntersectionTypeReflection extends \ReflectionIntersectionType
{
    /**
     * @param non-empty-list<\ReflectionNamedType> $types
     */
    public function __construct(
        private readonly array $types,
    ) {}

    public function allowsNull(): bool
    {
        return false;
    }

    public function getTypes(): array
    {
        return $this->types;
    }
}
