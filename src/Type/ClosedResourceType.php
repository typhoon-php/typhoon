<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\Type;
use Typhoon\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @implements Type<closed-resource>
 */
enum ClosedResourceType implements Type
{
    /**
     * @internal
     * @psalm-internal Typhoon
     */
    case type;

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClosedResource($this);
    }
}
