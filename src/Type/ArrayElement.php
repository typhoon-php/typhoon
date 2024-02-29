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
     * @param Type<TType> $type
     */
    public function __construct(
        public readonly Type $type,
        public readonly bool $optional = false,
    ) {}
}
