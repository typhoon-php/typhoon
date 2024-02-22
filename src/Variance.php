<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
enum Variance: string
{
    case Invariant = 'invariant';
    case Covariant = 'covariant';
    case Contravariant = 'contravariant';
    case Bivariant = 'bivariant';
}
