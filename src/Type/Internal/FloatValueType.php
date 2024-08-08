<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class FloatValueType implements Type
{
    public function __construct(
        private readonly float $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->floatValue($this, $this->value);
    }
}
