<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class KeyOfType implements Type
{
    public readonly Type $type;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    public function __construct(
        Type $type,
    ) {
        $this->type = $type;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitKeyOf($this);
    }
}
