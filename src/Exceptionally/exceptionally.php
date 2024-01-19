<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exceptionally;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template T
 * @template TOrFalse of T|false
 * @param callable(): TOrFalse $call
 * @return T
 */
function exceptionally(callable $call): mixed
{
    set_error_handler(static fn(int $level, string $message, string $file, int $line) => throw new \ErrorException(
        message: $message,
        severity: $level,
        filename: $file,
        line: $line,
    ));

    try {
        /** @var T */
        return $call();
    } finally {
        restore_error_handler();
    }
}
