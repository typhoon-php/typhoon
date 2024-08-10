<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpVersionChangeDetector::class)]
final class PhpVersionChangeDetectorTest extends TestCase
{
    public function testItDetectsVersionChanged(): void
    {
        $detector = new PhpVersionChangeDetector(50306);

        self::assertTrue($detector->changed());
    }

    public function testItDetectsVersionNotChanged(): void
    {
        $detector = new PhpVersionChangeDetector(\PHP_VERSION_ID);

        self::assertFalse($detector->changed());
    }

    public function testItDetectsVersionEmptyConstructorNotChanged(): void
    {
        $detector = new PhpVersionChangeDetector();

        self::assertFalse($detector->changed());
    }

    public function testItReturnsDeduplicatedDetectors(): void
    {
        $detector = ChangeDetectors::from([
            new PhpVersionChangeDetector(),
            $detector2 = new PhpVersionChangeDetector(\PHP_VERSION_ID),
            $detector3 = new PhpVersionChangeDetector(123),
            new PhpVersionChangeDetector(345),
            $detector5 = new PhpVersionChangeDetector(345),
        ]);

        $deduplicated = $detector->deduplicate();

        self::assertSame([$detector2, $detector3, $detector5], array_values($deduplicated));
    }

    public function testDeduplicateResult(): void
    {
        $detector = new PhpVersionChangeDetector(123);

        $deduplicate = $detector->deduplicate();

        self::assertSame(['Typhoon\ChangeDetector\PhpVersionChangeDetector.123' => $detector], $deduplicate);
    }
}
