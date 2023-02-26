<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements Type<literal-string>
 */
final class LiteralStringT implements Type
{
    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitLiteralString($this);
    }
}
