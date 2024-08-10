<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpExtensionVersionChangeDetector::class)]
final class PhpExtensionVersionChangeDetectorTest extends TestCase
{
    public function testItDetectsExtensionIsNowInstalled(): void
    {
        $detector = new PhpExtensionVersionChangeDetector('date', false);

        self::assertTrue($detector->changed());
    }

    public function testItDetectsExtensionIsNowUninstalled(): void
    {
        $detector = new PhpExtensionVersionChangeDetector('abc', '1.0.0');

        self::assertTrue($detector->changed());
    }

    public function testItDetectsExtensionVersionChanged(): void
    {
        $detector = new PhpExtensionVersionChangeDetector('date', '1.0.0');

        self::assertTrue($detector->changed());
    }

    public function testItDetectsExtensionVersionNotChanged(): void
    {
        $detector = PhpExtensionVersionChangeDetector::fromName('date');

        self::assertFalse($detector->changed());
    }

    public function testItDetectsNonInstalledExtensionStillNotInstalled(): void
    {
        $detector = new PhpExtensionVersionChangeDetector('abc', false);

        self::assertFalse($detector->changed());
    }

    public function testItReturnsDeduplicatedDetectors(): void
    {
        $detector = ChangeDetectors::from([
            new PhpExtensionVersionChangeDetector('a', false),
            $detector2 = new PhpExtensionVersionChangeDetector('a', false),
            $detector3 = new PhpExtensionVersionChangeDetector('a', '1.2'),
            new PhpExtensionVersionChangeDetector('b', '3.4'),
            $detector5 = new PhpExtensionVersionChangeDetector('b', '3.4'),
        ]);

        $deduplicated = $detector->deduplicate();

        self::assertSame([$detector2, $detector3, $detector5], array_values($deduplicated));
    }

    public function testFromNameThrowsForNonInstalledExtension(): void
    {
        $this->expectExceptionObject(new ExtensionIsNotInstalled('abc'));

        PhpExtensionVersionChangeDetector::fromName('abc');
    }

    public function testFromReflection(): void
    {
        $detector = PhpExtensionVersionChangeDetector::fromName('date');

        $fromReflection = PhpExtensionVersionChangeDetector::fromReflection(new \ReflectionExtension('date'));

        self::assertEquals($detector, $fromReflection);
    }

    #[TestWith([new PhpExtensionVersionChangeDetector('abc', '123'), 'Typhoon\ChangeDetector\PhpExtensionVersionChangeDetector.abc.123'])]
    #[TestWith([new PhpExtensionVersionChangeDetector('abc', false), 'Typhoon\ChangeDetector\PhpExtensionVersionChangeDetector.abc.false'])]
    public function testDeduplicateResult(PhpExtensionVersionChangeDetector $detector, string $expectedHash): void
    {
        $deduplicate = $detector->deduplicate();

        self::assertSame([$expectedHash => $detector], $deduplicate);
    }
}
