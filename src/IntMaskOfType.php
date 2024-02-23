<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TIntMask of int
 * @implements Type<TIntMask>
 */
final class IntMaskOfType implements Type
{
    /**
     * @param Type<TIntMask> $type
     */
    public function __construct(
        private readonly Type $type,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->intMaskOf($this, $this->type);
    }
}
