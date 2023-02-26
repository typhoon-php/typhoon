<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @template T
 * @param Type<T> $_type
 * @return T
 */
function extractType(Type $_type): mixed
{
    /** @var T */
    return null;
}
