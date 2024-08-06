<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\Type\Visitor\TypeStringifier;

/**
 * @api
 * @psalm-pure
 * @return non-empty-string
 */
function stringify(Type $type): string
{
    /** @psalm-suppress ImpureMethodCall */
    return $type->accept(TypeStringifier::Instance);
}
