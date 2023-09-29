<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 */
final class ArrayElement
{
    /**
     * @var Type<TType>
     */
    public readonly Type $type;

    public readonly bool $optional;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param Type<TType> $type
     */
    public function __construct(
        Type $type,
        bool $optional = false,
    ) {
        $this->optional = $optional;
        $this->type = $type;
    }
}
