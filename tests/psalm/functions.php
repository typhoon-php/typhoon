<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @template TType
 * @param Type<TType> $_type
 * @return TType
 */
function extractType(Type $_type): mixed
{
    /** @var TType */
    return null;
}
