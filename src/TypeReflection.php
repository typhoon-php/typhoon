<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 */
final class TypeReflection
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     */
    public function __construct(
        public readonly Type $resolved,
        public readonly ?Type $native,
        public readonly ?Type $phpDoc,
    ) {
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function resolve(TypeVisitor $typeResolver): self
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
