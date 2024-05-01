<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
final class FunctionId extends DeclarationId
{
    /**
     * @param non-empty-string $name
     */
    protected function __construct(
        public readonly string $name,
    ) {}

    public function toString(): string
    {
        return $this->name . '()';
    }

    public function equals(DeclarationId $id): bool
    {
        return $id instanceof self
            && $id->name === $this->name;
    }
}
