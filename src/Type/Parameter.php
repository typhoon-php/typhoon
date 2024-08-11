<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TType
 */
final class Parameter
{
    /**
     * @param Type<TType> $type
     */
    public function __construct(
        public readonly Type $type = types::mixed,
        public readonly bool $hasDefault = false,
        public readonly bool $variadic = false,
        public readonly bool $byReference = false,
    ) {}

    public function with(
        ?Type $type = null,
        ?bool $hasDefault = null,
        ?bool $variadic = null,
        ?bool $byReference = null,
    ): self {
        return new self(
            type: $type ?? $this->type,
            hasDefault: $hasDefault ?? $this->hasDefault,
            variadic: $variadic ?? $this->variadic,
            byReference: $byReference ?? $this->byReference,
        );
    }
}
