<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
enum Variance
{
    case Bivariant;
    case Contravariant;
    case Covariant;
    case Invariant;
}
