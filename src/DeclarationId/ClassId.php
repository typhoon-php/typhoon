<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
final class ClassId extends DeclarationId
{
    /**
     * @param non-empty-string $name
     */
    protected function __construct(
        private readonly string $name,
    ) {}

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function equals(DeclarationId $id): bool
    {
        return $id instanceof self
            && $id->name === $this->name;
    }
}
