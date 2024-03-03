<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;

/**
 * @api
 */
final class ParameterDoesNotExist extends ReflectionException
{
    public function __construct(AtFunction|AtMethod $at, string|int $nameOrPosition, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                '%s does not have a parameter %s',
                match (true) {
                    $at instanceof AtMethod => sprintf('%s::%s()', $at->class, $at->name),
                    $at instanceof AtFunction => sprintf('%s()', $at->name),
                },
                \is_int($nameOrPosition) ? 'at position ' . $nameOrPosition : sprintf('named "%s"', $nameOrPosition),
            ),
            previous: $previous,
        );
    }
}
