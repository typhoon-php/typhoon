<?php

declare(strict_types=1);

namespace Typhoon\TypedMap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KeyIsNotDefined::class)]
final class KeyIsNotDefinedTest extends TestCase
{
    public function testMessage(): void
    {
        $exception = new KeyIsNotDefined(Keys::A);

        self::assertSame('Key Typhoon\TypedMap\Keys::A is not defined in the TypedMap', $exception->getMessage());
    }
}
