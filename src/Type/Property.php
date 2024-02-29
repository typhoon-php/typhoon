<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TType
 */
final class Property
{
    /**
     * @param Type<TType> $type
     */
    public function __construct(
        public readonly Type $type,
        public readonly bool $optional = false,
    ) {}
}
