<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversFunction('Typhoon\Reflection\Internal\get_short_name')]
final class GetShortNameTest extends TestCase
{
    #[TestWith(['', ''])]
    #[TestWith(['a', 'a'])]
    #[TestWith(['a\b', 'b'])]
    #[TestWith(['a\b\\', ''])]
    #[TestWith(['\\', ''])]
    public function test(string $name, string $expectedShortName): void
    {
        $shortName = get_short_name($name);

        self::assertSame($expectedShortName, $shortName);
    }
}
