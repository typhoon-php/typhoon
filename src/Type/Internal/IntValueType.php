<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TValue of int
 * @implements Type<TValue>
 */
final class IntValueType implements Type
{
    /**
     * @param TValue $value
     */
    public function __construct(
        private readonly int $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->intValue($this, $this->value);
    }
}
