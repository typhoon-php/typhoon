<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @implements Type<void>
 */
enum VoidType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    case Type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitVoid($this);
    }
}
