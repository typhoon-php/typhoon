<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
enum TypeKind
{
    case Resolved;
    case Native;
    case Annotated;
    case Value;
}
