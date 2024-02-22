<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TType
 * @implements Type<TType>
 */
final class VarianceAwareType implements Type
{
    /**
     * @var Type<TType>
     */
    public readonly Type $type;

    public readonly Variance $variance;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param Type<TType> $type
     */
    public function __construct(
        Type $type,
        Variance $variance,
    ) {
        $this->type = $type;
        $this->variance = $variance;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitVarianceAware($this);
    }
}
