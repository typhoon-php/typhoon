<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TConstant
 * @implements Type<TConstant>
 */
final class ConstantType implements Type
{
    /**
     * @var non-empty-string
     */
    public readonly string $constant;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param non-empty-string $constant
     */
    public function __construct(
        string $constant,
    ) {
        $this->constant = $constant;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitConstant($this);
    }
}
