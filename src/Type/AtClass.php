<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
final class AtClass implements DeclaredAt
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
    ) {}

    public function equals(DeclaredAt $at): bool
    {
        return $at instanceof self && $this->name === $at->name;
    }
}
