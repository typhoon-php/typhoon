<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsNull extends Comparator
{
    public function null(Type $self): mixed
    {
        return true;
    }
}
