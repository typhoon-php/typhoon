<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 */
final class TypeReflection
{
    public function __construct(
        public readonly Type $resolved = types::mixed,
        public readonly ?Type $native = null,
        public readonly ?Type $phpDoc = null,
    ) {
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function withResolvedTypes(TypeVisitor $typeResolver): self
    {
        $resolved = $this->resolved->accept($typeResolver);

        if ($resolved === $this->resolved) {
            return $this;
        }

        return new self(
            resolved: $resolved,
            native: $this->native,
            phpDoc: $this->phpDoc,
        );
    }
}
