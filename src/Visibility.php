<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

/**
 * @api
 * @psalm-immutable
 */
enum Visibility
{
    case PRIVATE;
    case PROTECTED;
    case PUBLIC;
}
