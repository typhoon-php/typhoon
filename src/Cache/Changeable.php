<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Cache;

interface Changeable
{
    public function changed(): bool;
}
