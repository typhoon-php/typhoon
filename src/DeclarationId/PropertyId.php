<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
final class PropertyId extends DeclarationId
{
    /**
     * @param non-empty-string $name
     */
    protected function __construct(
        public readonly ClassId|AnonymousClassId $class,
        public readonly string $name,
    ) {}

    public function toString(): string
    {
        return sprintf('%s::$%s', $this->class->toString(), $this->name);
    }

    public function equals(DeclarationId $id): bool
    {
        return $id instanceof self
            && $id->class->equals($this->class)
            && $id->name === $this->name;
    }
}
