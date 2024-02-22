<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @implements Type<never>
 */
enum NeverType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    case Type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNever($this);
    }
}
