<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @implements Type<int>
 */
final class IntRangeType implements Type
{
    public function __construct(
        private readonly ?int $min,
        private readonly ?int $max,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->intRange($this, $this->min, $this->max);
    }
}
