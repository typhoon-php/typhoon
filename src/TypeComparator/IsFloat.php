<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsFloat extends Comparator
{
    public function float(Type $self): mixed
    {
        return true;
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return \is_float($value);
    }
}
