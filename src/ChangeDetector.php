<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

interface ChangeDetector
{
    public function changed(): bool;
}
