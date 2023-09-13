<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\Type;
use Typhoon\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TValue of int
 * @implements Type<TValue>
 */
final class IntLiteralType implements Type
{
    /**
     * @var TValue
     */
    public readonly int $value;

    /**
     * @internal
     * @psalm-internal Typhoon
     * @param TValue $value
     */
    public function __construct(
        int $value,
    ) {
        $this->value = $value;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntLiteral($this);
    }
}
