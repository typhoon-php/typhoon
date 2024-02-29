<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsResource extends Comparator
{
    public function resource(Type $self): mixed
    {
        return true;
    }
}
