<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class MultipleAnonymousClassesOnLine extends ReflectionException
{
    /**
     * @param non-empty-string $file
     * @param non-negative-int $line
     */
    public function __construct(string $file, int $line, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Multiple anonymous classes are declared at %s:%d', $file, $line), previous: $previous);
    }
}
