<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @implements Type<null>
 */
enum NullType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    case Type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNull($this);
    }
}
