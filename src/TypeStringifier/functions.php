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
    /** @var non-empty-string */
    return strtr($type->accept(new TypeStringifier()), [
        'true|false' => 'bool',
        'true|false|int|float|string' => 'scalar',
        'Closure&callable' => 'Closure',
        'numeric&string' => 'numeric-string',
        'truthy&string' => 'truthy-string',
    ]);
}
