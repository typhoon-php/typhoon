<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
abstract class TypeAlias implements Type
{
    final public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitAlias($this);
    }

    /**
     * @return Type<TType>
     */
    abstract public function type(): Type;
}
