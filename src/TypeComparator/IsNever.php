<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsNever extends Comparator
{
    public function never(Type $self): mixed
    {
        return true;
    }
}
