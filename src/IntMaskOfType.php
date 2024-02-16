<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TIntMask of positive-int
 * @implements Type<TIntMask>
 */
final class IntMaskOfType implements Type
{
    /**
     * @var Type<TIntMask>
     */
    public readonly Type $type;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param Type<TIntMask> $type
     */
    public function __construct(
        Type $type,
    ) {
        $this->type = $type;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntMaskOf($this);
    }
}
