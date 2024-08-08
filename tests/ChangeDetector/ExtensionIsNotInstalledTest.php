<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExtensionIsNotInstalled::class)]
final class ExtensionIsNotInstalledTest extends TestCase
{
    public function testMessage(): void
    {
        $exception = new ExtensionIsNotInstalled('pcntl');

        self::assertSame('PHP extension "pcntl" is not installed', $exception->getMessage());
    }

    public function testPreviousPreserved(): void
    {
        $previous = new \Exception();

        $exception = new ExtensionIsNotInstalled('pcntl', $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
