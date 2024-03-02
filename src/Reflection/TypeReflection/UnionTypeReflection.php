<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class UnionTypeReflection extends \ReflectionUnionType
{
    /**
     * @param non-empty-list<\ReflectionNamedType|\ReflectionIntersectionType> $types
     */
    public function __construct(
        private readonly array $types,
    ) {}

    public function allowsNull(): bool
    {
        foreach ($this->types as $type) {
            if ($type->allowsNull()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement, UnusedPsalmSuppress
     */
    public function getTypes(): array
    {
        return $this->types;
    }
}
