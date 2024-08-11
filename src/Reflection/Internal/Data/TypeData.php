<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\TypeKind;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon
 */
final class TypeData
{
    public function __construct(
        /** @readonly */
        public ?Type $native = null,
        /** @readonly */
        public ?Type $annotated = null,
        /** @readonly */
        public ?Type $tentative = null,
        /** @readonly */
        public ?Type $inferred = null,
    ) {}

    public function withNative(?Type $native): self
    {
        $data = clone $this;
        $data->native = $native;

        return $data;
    }

    public function withAnnotated(?Type $annotated): self
    {
        $data = clone $this;
        $data->annotated = $annotated;

        return $data;
    }

    public function withTentative(?Type $tentative): self
    {
        $data = clone $this;
        $data->tentative = $tentative;

        return $data;
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function inherit(TypeVisitor $typeResolver): self
    {
        return new self(
            native: $this->native?->accept($typeResolver),
            annotated: $this->annotated?->accept($typeResolver),
            tentative: $this->tentative?->accept($typeResolver),
            inferred: $this->inferred?->accept($typeResolver),
        );
    }

    /**
     * @return ($kind is TypeKind::Resolved ? Type : ?Type)
     */
    public function get(TypeKind $kind = TypeKind::Resolved): ?Type
    {
        return match ($kind) {
            TypeKind::Resolved => $this->annotated ?? $this->inferred ?? $this->tentative ?? $this->native ?? types::mixed,
            TypeKind::Native => $this->native,
            TypeKind::Tentative => $this->tentative,
            TypeKind::Inferred => $this->inferred,
            TypeKind::Annotated => $this->annotated,
        };
    }
}
