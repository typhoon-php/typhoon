<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
final class MethodId extends DeclarationId
{
    /**
     * @param non-empty-string $name
     */
    protected function __construct(
        public readonly ClassId|AnonymousClassId $classId,
        public readonly string $name,
    ) {}

    public function toString(): string
    {
        return sprintf('method(%s, %s)', $this->classId->toString(), $this->name);
    }
}
