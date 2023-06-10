<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\TypeVisitor;

final class ParameterReflection extends Reflection
{
    /**
     * @param 0|positive-int $position
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly int $position,
        public readonly string $name,
        public readonly bool $promoted,
        public readonly bool $variadic,
        public readonly bool $hasDefaultValue,
        public readonly TypeReflection $type,
    ) {
    }

    public function isOptional(): bool
    {
        return $this->variadic || $this->hasDefaultValue;
    }

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        $data = get_object_vars($this);
        $data['type'] = $this->type->withResolvedTypes($typeResolver);

        return new self(...$data);
    }

    protected function toChildOf(Reflection $parent): static
    {
        $data = get_object_vars($this);
        $data['type'] = $this->type->toChildOf($parent->type);

        return new self(...$data);
    }
}
