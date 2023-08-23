<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ChangeDetector;

use Typhoon\Reflection\ChangeDetector;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @api
 */
final class PhpVersionChangeDetector implements ChangeDetector
{
    /**
     * @var non-empty-string
     */
    private readonly string $version;

    /**
     * @param ?non-empty-string $extension
     */
    public function __construct(
        private readonly ?string $extension = null,
    ) {
        /** @var non-empty-string */
        $this->version = exceptionally(static fn (): string|false => phpversion($extension));
    }

    public function changed(): bool
    {
        return phpversion($this->extension) === $this->version;
    }
}
