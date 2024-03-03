<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\Reflection\ReflectionException;
use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;

/**
 * @api
 */
final class TemplateDoesNotExist extends ReflectionException
{
    public function __construct(AtClass|AtFunction|AtMethod $at, string|int $nameOrPosition, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                '%s does not have a template %s',
                match (true) {
                    $at instanceof AtClass => $at->name,
                    $at instanceof AtMethod => sprintf('%s::%s()', $at->class, $at->name),
                    $at instanceof AtFunction => sprintf('%s()', $at->name),
                },
                \is_int($nameOrPosition) ? 'at position ' . $nameOrPosition : sprintf('named "%s"', $nameOrPosition),
            ),
            previous: $previous,
        );
    }
}
