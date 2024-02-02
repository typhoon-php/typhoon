<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
final class ReflectionException extends \LogicException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = strtr($message, "\0", '\0');

        parent::__construct($message, $code, $previous);
    }
}
