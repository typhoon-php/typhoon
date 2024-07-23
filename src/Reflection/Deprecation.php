<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 */
final class Deprecation
{
    /**
     * @param ?non-empty-string $message
     * @param ?non-empty-string $since
     */
    public function __construct(
        public readonly ?string $message = null,
        public readonly ?string $since = null,
    ) {}
}
