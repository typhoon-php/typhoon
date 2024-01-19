<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\TypeResolver\StaticResolver;
use Typhoon\Reflection\TypeResolver\TemplateResolver;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @api
 */
final class TypeReflection
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

    public static function fromPrototype(self $prototype, self $child): self
    {
        if ($child->phpDoc !== null) {
            return $child;
        }

        if ($prototype->phpDoc === null) {
            return $child;
        }

        if ($prototype->native !== $child->native) {
            return $child;
        }

        return new self(
            native: $child->native,
            phpDoc: $child->phpDoc,
            resolved: $prototype->phpDoc,
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

    public function resolve(TemplateResolver|StaticResolver $typeResolver): self
    {
        return new self(
            native: $this->native,
            phpDoc: $this->phpDoc,
            resolved: $this->resolved->accept($typeResolver),
        );
    }

    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    private function __clone() {}
}
