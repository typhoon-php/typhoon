<?php

declare(strict_types=1);

namespace Typhoon\Type\IntMaskOfTest;

use Typhoon\Type\IntMaskOfType;
use Typhoon\Type\Type;
use function Typhoon\Type\extractType;

final class X
{
    const A = 1;
    const B = 2;
    const C = 4;
}

/**
 * @param Type<X::*> $constantType
 */
function testItPreservesPassedType(Type $constantType): void
{
    /** @psalm-check-type-exact $_int = 1|2|4 */
    $_int = extractType(new IntMaskOfType($constantType));
}
