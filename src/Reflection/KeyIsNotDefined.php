<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
final class KeyIsNotDefined extends \RuntimeException
{
    public function __construct(int|string $key)
    {
        if (\is_string($key)) {
            $key = \sprintf('"%s"', addcslashes($key, '"'));
        }

        parent::__construct(\sprintf('Key %s is not defined in the Collection', $key));
    }
}
