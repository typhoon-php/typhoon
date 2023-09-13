<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class NullClassLocator implements ClassLocator
{
    public function locateClass(string $name): ?Resource
    {
        return null;
    }
}
