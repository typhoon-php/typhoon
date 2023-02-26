<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * This interface must not be implemented outside PHP\ExtendedTypeSystem\Type!
 *
 * @psalm-api
 * @psalm-immutable
 * @template-covariant T
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
