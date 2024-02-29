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
    public function int(Type $self): mixed
    {
        return true;
    }

    public function intMask(Type $self, Type $type): mixed
    {
        return true;
    }

    public function intRange(Type $self, ?int $min, ?int $max): mixed
    {
        return true;
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return \is_int($value);
    }
}
