<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 */
interface Type
{
    /**
     * @template TReturn
     * @param TypeVisitor<TReturn> $visitor
     * @return TReturn
     */
    public function accept(TypeVisitor $visitor): mixed;
}
