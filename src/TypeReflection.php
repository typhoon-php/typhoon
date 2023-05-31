<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 */
final class TypeReflection
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem\Reflection
     */
    public function __construct(
        public readonly Type $resolved,
        public readonly ?Type $native,
        public readonly ?Type $phpDoc,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function fromNative(Type $nativeType): self
    {
        return new self($nativeType, $nativeType, null);
    }

    /**
     * @psalm-pure
     */
    public static function fromPHPDoc(Type $phpDocType): self
    {
        return new self($phpDocType, null, $phpDocType);
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function resolveTypes(TypeVisitor $typeResolver): self
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
