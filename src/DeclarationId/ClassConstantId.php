<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @readonly
 */
final class ClassConstantId extends DeclarationId
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly ClassId|AnonymousClassId $classId,
        public readonly string $name,
    ) {}

    public function toString(): string
    {
        return sprintf('class-constant(%s, %s)', $this->classId->toString(), $this->name);
    }
}
