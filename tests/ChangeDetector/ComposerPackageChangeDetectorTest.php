<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComposerPackageChangeDetector::class)]
final class ComposerPackageChangeDetectorTest extends TestCase
{
    public function testItDetectsPackageRefChange(): void
    {
        $changeDetector = new ComposerPackageChangeDetector('phpunit/phpunit', 'wrong-ref');

        $changed = $changeDetector->changed();

        self::assertTrue($changed);
    }

    public function testItReturnsDeduplicatedDetectors(): void
    {
        $detector = ChangeDetectors::from([
            new ComposerPackageChangeDetector('php', 'ref'),
            new ComposerPackageChangeDetector('php', 'another-ref'),
            new ComposerPackageChangeDetector('test', 'ref'),
        ]);

        $deduplicated = $detector->deduplicate();

        self::assertCount(3, $deduplicated);
    }

    public function testFromNameReturnsConsistentReference(): void
    {
        $changeDetector1 = ComposerPackageChangeDetector::fromName('phpunit/phpunit');
        $changeDetector2 = ComposerPackageChangeDetector::fromName('phpunit/phpunit');

        self::assertEquals($changeDetector1, $changeDetector2);
    }

    public function testFromNameThrowsOnNonInstalledPackage(): void
    {
        $this->expectExceptionObject(new PackageIsNotInstalled('abc'));

        ComposerPackageChangeDetector::fromName('abc');
    }

    #[TestWith([new ComposerPackageChangeDetector('abc', '123'), 'Typhoon\ChangeDetector\ComposerPackageChangeDetector.abc.123'])]
    #[TestWith([new ComposerPackageChangeDetector('abc', null), 'Typhoon\ChangeDetector\ComposerPackageChangeDetector.abc.'])]
    public function testDeduplicateResult(ComposerPackageChangeDetector $detector, string $expectedHash): void
    {
        $deduplicate = $detector->deduplicate();

        self::assertSame([$expectedHash => $detector], $deduplicate);
    }
}
