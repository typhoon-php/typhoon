<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

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

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function withResolved(Type $type): self
    {
        return new self(
            native: $this->native,
            phpDoc: $this->phpDoc,
            resolved: $type,
        );
    }

    private function __clone() {}
}
