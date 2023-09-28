<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TIntMask of int<0, max>
 * @implements Type<TIntMask>
 */
final class IntMaskType implements Type
{
    /**
     * @var non-empty-list<int<0, max>>
     */
    public readonly array $ints;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param non-empty-list<int<0, max>> $ints
     */
    public function __construct(
        array $ints,
    ) {
        $this->ints = $ints;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitIntMask($this);
    }
}
