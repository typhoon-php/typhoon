<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @implements Type<false>
 */
enum FalseType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitFalse($this);
    }
}
