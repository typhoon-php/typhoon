<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeReflection;
use ExtendedTypeSystem\types;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class TypeReflectionBuilder
{
    private bool $declared = false;
    private ?Type $nativeType = null;
    private ?Type $phpDocType = null;

    /**
     * @var list<TypeReflection>
     */
    private array $prototypes = [];

    public function nativeType(?Type $type): self
    {
        $this->declared = true;
        $this->nativeType = $type;

        return $this;
    }

    public function phpDocType(?Type $type): self
    {
        $this->declared = true;
        $this->phpDocType = $type;

        return $this;
    }

    public function addPrototype(TypeReflection $type): self
    {
        $this->prototypes[] = $type;

        return $this;
    }

    public function build(): TypeReflection
    {
        if (!$this->declared) {
            return $this->prototypes[0] ?? throw new \LogicException();
        }

        return new TypeReflection(
            resolved: $this->resolveType(),
            native: $this->nativeType,
            phpDoc: $this->phpDocType,
        );
    }

    private function resolveType(): Type
    {
        if ($this->phpDocType !== null) {
            return $this->phpDocType;
        }

        // todo use real type comparison
        if (\count($this->prototypes) === 1 && ($this->nativeType === null || $this->nativeType === $this->prototypes[0]->native)) {
            return $this->prototypes[0]->resolved;
        }

        return $this->nativeType ?? types::mixed;
    }
}
