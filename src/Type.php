<?php

declare(strict_types=1);

namespace Typhoon\Type;

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
