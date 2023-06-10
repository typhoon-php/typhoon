<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\TypeVisitor;

final class PropertyReflection extends Reflection
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $static,
        public readonly bool $promoted,
        public readonly bool $hasDefaultValue,
        public readonly bool $readonly,
        public readonly Visibility $visibility,
        public readonly TypeReflection $type,
    ) {
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
