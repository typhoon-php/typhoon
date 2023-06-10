<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use ExtendedTypeSystem\TypeVisitor;

final class TypeReflection extends Reflection
{
    public function __construct(
        public readonly ?Type $native,
        public readonly ?Type $phpDoc,
    ) {
    }

    public function resolve(): Type
    {
        return $this->phpDoc ?? $this->native ?? types::mixed;
    }

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        return new self(
            native: $this->native?->accept($typeResolver),
            phpDoc: $this->phpDoc?->accept($typeResolver),
        );
    }

    protected function toChildOf(Reflection $parent): static
    {
        if ($this->phpDoc !== null) {
            return $this;
        }

        if ($parent->phpDoc === null) {
            return $this;
        }

        if ($parent->native !== $this->native) {
            return $this;
        }

        return new self(
            native: $this->native,
            phpDoc: $parent->phpDoc,
        );
    }
}
