<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
enum Variance
{
    case INVARIANT;
    case COVARIANT;
    case CONTRAVARIANT;
    case BIVARIANT;
}
