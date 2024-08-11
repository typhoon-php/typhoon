<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TType
 */
final class ShapeElement
{
    /**
     * @param Type<TType> $type
     */
    public function __construct(
        public readonly Type $type = types::mixed,
        public readonly bool $optional = false,
    ) {}

    public function with(?Type $type = null, ?bool $optional = null): self
    {
        return new self(
            type: $type ?? $this->type,
            optional: $optional ?? $this->optional,
        );
    }
}
