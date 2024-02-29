<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 * @extends DefaultTypeVisitor<bool>
 */
final class IsMixed extends DefaultTypeVisitor
{
    protected function default(Type $self): mixed
    {
        return true;
    }
}
