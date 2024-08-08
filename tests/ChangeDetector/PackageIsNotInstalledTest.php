<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageIsNotInstalled::class)]
final class PackageIsNotInstalledTest extends TestCase
{
    public function testMessage(): void
    {
        $exception = new PackageIsNotInstalled('typhoon/type');

        self::assertSame('Package "typhoon/type" is not installed via Composer', $exception->getMessage());
    }

    public function testPreviousPreserved(): void
    {
        $previous = new \Exception();

        $exception = new PackageIsNotInstalled('typhoon/type', $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
