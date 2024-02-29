<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Typhoon\Reflection\Origin;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TypeMetadata
{
    private function __construct(
        public readonly ?Type $native,
        public readonly ?Type $phpDoc,
        public readonly Type $resolved,
    ) {}

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public static function create(?Type $native = null, ?Type $phpDoc = null): self
    {
        return new self(
            native: $native,
            phpDoc: $phpDoc,
            resolved: $phpDoc ?? $native ?? types::mixed,
        );
    }

    public function get(Origin $origin = Origin::Resolved): ?Type
    {
        return match ($origin) {
            Origin::Native => $this->native,
            Origin::PhpDoc => $this->phpDoc,
            Origin::Resolved => $this->resolved,
        };
    }

    public function withResolved(Type $type): self
    {
        return new self(
            native: $this->native,
            phpDoc: $this->phpDoc,
            resolved: $type,
        );
    }
}
