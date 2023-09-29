<?php

declare(strict_types=1);

namespace Typhoon\Type\StaticTest;

use Typhoon\Type\StaticType;

use function Typhoon\Type\extractType;

/**
 * @param StaticType<\stdClass> $type
 */
function testStatic(StaticType $type): void
{
    /** @psalm-check-type-exact $_class = \stdClass */
    $_class = extractType($type);
}
