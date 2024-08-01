<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use Typhoon\Reflection\Internal\Inheritance\TypeResolver;
use Typhoon\Reflection\TypeKind;
use Typhoon\Type\Type;
use Typhoon\Type\types;

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
        public ?Type $value = null,
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

    public function inherit(TypeResolver $typeResolver): self
    {
        return new self(
            native: $typeResolver->resolveNativeType($this->native),
            annotated: $typeResolver->resolveType($this->annotated),
            tentative: $typeResolver->resolveNativeType($this->tentative),
            value: $typeResolver->resolveNativeType($this->value),
        );
    }

    /**
     * @return ($kind is TypeKind::Resolved ? Type : ?Type)
     */
    public function get(TypeKind $kind = TypeKind::Resolved): ?Type
    {
        return match ($kind) {
            TypeKind::Resolved => $this->annotated ?? $this->value ?? $this->tentative ?? $this->native ?? types::mixed,
            TypeKind::Native => $this->native,
            TypeKind::Inferred => $this->value,
            TypeKind::Annotated => $this->annotated,
        };
    }
}
