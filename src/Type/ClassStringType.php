<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\Type;
use Typhoon\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @implements Type<class-string>
 */
enum ClassStringType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClassString($this);
    }
}
