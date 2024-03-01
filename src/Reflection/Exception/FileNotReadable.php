<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class FileNotReadable extends ReflectionException
{
    public function __construct(string $file, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('File "%s" does not exist or is not readable', $file), previous: $previous);
    }
}
