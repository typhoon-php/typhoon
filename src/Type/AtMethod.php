<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
final class AtMethod implements DeclaredAt
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $class,
        public readonly string $name,
    ) {}

    public function equals(DeclaredAt $at): bool
    {
        return $at instanceof self && $this->class === $at->class && $this->name === $at->name;
    }
}
