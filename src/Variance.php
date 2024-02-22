<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
enum Variance
{
    case Invariant;
    case Covariant;
    case Contravariant;
    case Bivariant;
}
