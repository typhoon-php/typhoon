<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
enum At implements DeclaredAt
{
    case anonymousClass;
    case anonymousFunction;

    public function equals(DeclaredAt $at): bool
    {
        return $this === $at;
    }
}
