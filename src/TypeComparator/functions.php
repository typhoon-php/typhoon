<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\Type;

/**
 * @api
 */
function isSubtype(Type $type, Type $of): bool
{
    return $type->accept($of->accept(new ComparatorSelector()));
}

/**
 * @api
 */
function areEqual(Type $type1, Type $type2): bool
{
    return isSubtype($type1, $type2) && isSubtype($type2, $type1);
}
