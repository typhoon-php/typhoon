<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
abstract class ReflectionException extends \ReflectionException
{
    final public static function normalizeClass(string $class): string
    {
        $nullBytePosition = strpos($class, "\0");

        if ($nullBytePosition === false) {
            return $class;
        }

        return substr($class, 0, $nullBytePosition);
    }
}
