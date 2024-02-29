<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;
use Typhoon\Type\Variance;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TType
 * @implements Type<TType>
 */
final class VarianceAwareType implements Type
{
    /**
     * @param Type<TType> $type
     */
    public function __construct(
        private readonly Type $type,
        private readonly Variance $variance,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->varianceAware($this, $this->type, $this->variance);
    }
}
