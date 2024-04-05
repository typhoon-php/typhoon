<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
interface DeclaredAt
{
    public function equals(self $at): bool;
}
