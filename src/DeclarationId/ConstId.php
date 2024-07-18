<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 */
final class ConstId extends Id
{
    /**
     * @param non-empty-string $name
     */
    protected function __construct(
        public readonly string $name,
    ) {}

    public function describe(): string
    {
        return 'constant ' . $this->name;
    }

    public function equals(mixed $value): bool
    {
        return $value instanceof self
            && $value->name === $this->name;
    }

    public function jsonSerialize(): array
    {
        return [self::CODE_CONST, $this->name];
    }
}
