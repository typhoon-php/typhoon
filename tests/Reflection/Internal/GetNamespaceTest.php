<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversFunction('Typhoon\Reflection\Internal\get_namespace')]
final class GetNamespaceTest extends TestCase
{
    #[TestWith(['', ''])]
    #[TestWith(['a', ''])]
    #[TestWith(['a\b', 'a'])]
    #[TestWith(['a\b\\', 'a\b'])]
    #[TestWith(['\\', ''])]
    public function test(string $name, string $expectedNamespace): void
    {
        $namespace = get_namespace($name);

        self::assertSame($expectedNamespace, $namespace);
    }
}
