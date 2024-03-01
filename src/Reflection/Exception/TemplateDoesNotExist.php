<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;

/**
 * @api
 */
final class TemplateDoesNotExist extends ReflectionException
{
    public function __construct(string|int $nameOrPosition, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Template %s does not exist', \is_int($nameOrPosition) ? $nameOrPosition : sprintf('"%s"', $nameOrPosition)),
            previous: $previous,
        );
    }
}
