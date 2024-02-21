<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TType
 * @implements Type<TType>
 */
final class NonEmptyType implements Type
{
    /**
     * @var Type<TType>
     */
    public readonly Type $type;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param Type<TType> $type
     */
    public function __construct(
        Type $type,
    ) {
        $this->type = $type;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNonEmpty($this);
    }
}
