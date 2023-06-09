<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TObject of object
 * @implements Type<class-string<TObject>>
 */
final class NamedClassStringType implements Type
{
    /**
     * @var Type<TObject>
     */
    public readonly Type $type;

    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param Type<TObject> $type
     */
    public function __construct(
        Type $type,
    ) {
        $this->type = $type;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNamedClassString($this);
    }
}
