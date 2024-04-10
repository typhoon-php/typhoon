<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsInt extends Comparator
{
    public function __construct(
        private readonly ?int $min,
        private readonly ?int $max,
    ) {}

    public function intMask(Type $self, Type $type): mixed
    {
        if ($this->min === null && $this->max === null) {
            return true;
        }

        // TODO
        return false;
    }

    public function int(Type $self, ?int $min = null, ?int $max = null): mixed
    {
        return ($this->min === null || ($min !== null && $min >= $this->min))
            && ($this->max === null || ($max !== null && $max <= $this->max));
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return \is_int($value)
            && ($this->min === null || $value >= $this->min)
            && ($this->max === null || $value <= $this->max);
    }
}
