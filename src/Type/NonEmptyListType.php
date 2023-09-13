<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\Type;
use Typhoon\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TValue
 * @implements Type<non-empty-list<TValue>>
 */
final class NonEmptyListType implements Type
{
    /**
     * @var Type<TValue>
     */
    public readonly Type $valueType;

    /**
     * @internal
     * @psalm-internal Typhoon
     * @param Type<TValue> $valueType
     */
    public function __construct(
        Type $valueType = MixedType::type,
    ) {
        $this->valueType = $valueType;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNonEmptyList($this);
    }
}
