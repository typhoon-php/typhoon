<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Reflector\FriendlyReflection;
use Typhoon\Reflection\TypeResolver\ClassTemplateResolver;
use Typhoon\Reflection\TypeResolver\StaticResolver;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @api
 */
final class TypeReflection extends FriendlyReflection
{
    private function __construct(
        private readonly ?Type $native,
        private readonly ?Type $phpDoc,
        private readonly Type $resolved,
    ) {}

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public static function create(?Type $native, ?Type $phpDoc): self
    {
        return new self(
            native: $native,
            phpDoc: $phpDoc,
            resolved: $phpDoc ?? $native ?? types::mixed,
        );
    }

    public function getNative(): ?Type
    {
        return $this->native;
    }

    public function getPhpDoc(): ?Type
    {
        return $this->phpDoc;
    }

    public function getResolved(): Type
    {
        return $this->resolved;
    }

    public function resolve(ClassTemplateResolver|StaticResolver $typeResolver): self
    {
        return new self(
            native: $this->native,
            phpDoc: $this->phpDoc,
            resolved: $this->resolved->accept($typeResolver),
        );
    }

    protected function toChildOf(FriendlyReflection $parent): static
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
            phpDoc: $this->phpDoc,
            resolved: $parent->phpDoc,
        );
    }

    private function __clone() {}
}
