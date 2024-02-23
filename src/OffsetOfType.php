<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<mixed>
 */
final class OffsetOfType implements Type
{
    public function __construct(
        private readonly Type $type,
        private readonly Type $offset,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->offsetOf($this, $this->type, $this->offset);
    }
}
