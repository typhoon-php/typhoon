<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
enum Origin
{
    case Native;
    case PhpDoc;
    case Resolved;
}
