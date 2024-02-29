<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsNumericString extends Comparator
{
    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return \is_string($value) && is_numeric($value);
    }

    public function numericString(Type $self): mixed
    {
        return true;
    }
}
