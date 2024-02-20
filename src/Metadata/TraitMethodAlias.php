<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-type Visibility = null|\ReflectionMethod::IS_PUBLIC|\ReflectionMethod::IS_PROTECTED|\ReflectionMethod::IS_PRIVATE
 */
final class TraitMethodAlias
{
    /**
     * @param Visibility $visibility
     * @param ?non-empty-string $alias
     */
    public function __construct(
        public readonly ?int $visibility = null,
        public readonly ?string $alias = null,
    ) {}
}
