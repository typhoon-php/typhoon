<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsBool extends Comparator
{
    public function bool(Type $self): mixed
    {
        return true;
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return \is_bool($value);
    }
}
