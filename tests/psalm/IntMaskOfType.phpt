--FILE--
<?php

namespace Typhoon\Type;

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
    /** @psalm-check-type-exact $_type = 1|2|4 */
    $_type = PsalmTest::extractType(new IntMaskOfType($constantType));
}

--EXPECT--
