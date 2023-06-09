<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class KeyOfType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     */
    public function __construct(
        public readonly Type $type,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitKeyOf($this);
    }
}
