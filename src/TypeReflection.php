<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class TypeReflection implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TType> $resolved
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

    public function accept(TypeVisitor $visitor): mixed
    {
        return $this->resolved->accept($visitor);
    }
}
