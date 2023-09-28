<?php

declare(strict_types=1);

namespace Typhoon\Type\IntMaskTest;

use Typhoon\Type\IntMaskType;
use function Typhoon\Type\extractType;

/**
 * @param IntMaskType<int-mask<1, 2, 4>> $type
 */
function a(IntMaskType $type): void
{
    /** @psalm-check-type-exact $_int = int-mask<1, 2, 4> */
    $_int = extractType($type);
}
