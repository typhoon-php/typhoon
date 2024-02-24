<?php

declare(strict_types=1);

namespace Typhoon\TypeStringifier;

use Typhoon\Type\Type;

/**
 * @api
 * @psalm-pure
 * @return non-empty-string
 * @psalm-suppress ImpureMethodCall
 */
function stringify(Type $type): string
{
    return $type->accept(new TypeStringifier());
}
