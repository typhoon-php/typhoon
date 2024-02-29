<?php

declare(strict_types=1);

namespace Typhoon\TypeStringifier;

use Typhoon\Type\Type;

/**
 * @api
 * @psalm-pure
 * @return non-empty-string
 */
function stringify(Type $type): string
{
    return $type->accept(new TypeStringifier());
}
