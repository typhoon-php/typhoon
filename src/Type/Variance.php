<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
enum Variance
{
    case Invariant;
    case Covariant;
    case Contravariant;
    case Bivariant;
}
