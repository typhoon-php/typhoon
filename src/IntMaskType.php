<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TIntMask of int
 * @implements Type<TIntMask>
 */
final class IntMaskType implements Type
{
    /**
     * @param non-empty-list<int> $ints
     */
    public function __construct(
        private readonly array $ints,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->intMask($this, $this->ints);
    }
}
