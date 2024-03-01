<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class UnionType implements Type
{
    /**
     * @param non-empty-list<Type<TType>> $types
     */
    public function __construct(
        private readonly array $types,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->union($this, $this->types);
    }
}
