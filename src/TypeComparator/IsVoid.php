<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsVoid extends Comparator
{
    public function void(Type $self): mixed
    {
        return true;
    }
}
