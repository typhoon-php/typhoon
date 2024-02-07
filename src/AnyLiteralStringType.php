<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @implements Type<literal-string>
 */
enum AnyLiteralStringType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitAnyLiteralString($this);
    }
}
