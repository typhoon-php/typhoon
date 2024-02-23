<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TConstant
 * @implements Type<TConstant>
 */
final class ConstantType implements Type
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->constant($this, $this->name);
    }
}
