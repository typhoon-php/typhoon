<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
final class ParameterId extends DeclarationId
{
    /**
     * @param non-empty-string $name
     */
    protected function __construct(
        public readonly MethodId $functionId,
        public readonly string $name,
    ) {}

    public function toString(): string
    {
        return sprintf('parameter(%s, %s)', $this->functionId->toString(), $this->name);
    }
}
