<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @implements Type<truthy-string>
 */
enum TruthyStringType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitTruthyString($this);
    }
}
