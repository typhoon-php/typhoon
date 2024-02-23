<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<mixed>
 */
final class OffsetType implements Type
{
    public function __construct(
        private readonly Type $type,
        private readonly Type $offset,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->offset($this, $this->type, $this->offset);
    }
}
