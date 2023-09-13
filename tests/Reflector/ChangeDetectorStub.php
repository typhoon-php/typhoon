<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ChangeDetector;

final class ChangeDetectorStub implements ChangeDetector
{
    public function __construct(
        private readonly bool $changed,
    ) {}

    public function changed(): bool
    {
        return $this->changed;
    }
}
