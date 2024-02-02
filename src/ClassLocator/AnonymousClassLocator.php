<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ClassLocator;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class AnonymousClassLocator implements ClassLocator
{
    public function locateClass(string $name): null|Resource|\ReflectionClass
    {
        $anonymousName = AnonymousClassName::tryFromString($name);

        if ($anonymousName === null) {
            return null;
        }

        return new Resource($anonymousName->file);
    }
}
