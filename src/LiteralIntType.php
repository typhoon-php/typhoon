<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @implements Type<literal-int>
 */
enum LiteralIntType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitLiteralInt($this);
    }
}
