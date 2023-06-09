<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TReturn
 * @implements Type<callable(): TReturn>
 */
final class CallableType implements Type
{
    /**
     * @internal
     * @psalm-internal ExtendedTypeSystem
     * @param list<Parameter> $parameters
     * @param ?Type<TReturn> $returnType
     */
    public function __construct(
        public readonly array $parameters = [],
        public readonly ?Type $returnType = null,
    ) {
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitCallable($this);
    }
}
