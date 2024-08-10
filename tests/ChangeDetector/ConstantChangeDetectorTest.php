<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConstantChangeDetector::class)]
final class ConstantChangeDetectorTest extends TestCase
{
    public function testItDetectsConstantAppeared(): void
    {
        $detector = new ConstantChangeDetector(
            name: 'PHP_VERSION_ID',
            exists: false,
            value: null,
        );

        $changed = $detector->changed();

        self::assertTrue($changed);
    }

    public function testItDetectsExistingConstantDidNotChange(): void
    {
        $detector = ConstantChangeDetector::fromName('PHP_VERSION_ID');

        $changed = $detector->changed();

        self::assertFalse($changed);
    }

    public function testItDetectsConstantDisappeared(): void
    {
        $detector = new ConstantChangeDetector(name: 'ABC', exists: true, value: 'abc');

        $changed = $detector->changed();

        self::assertTrue($changed);
    }

    public function testItDetectsNonExistingConstantDidNotChange(): void
    {
        $detector = new ConstantChangeDetector('A', false, null);

        $changed = $detector->changed();

        self::assertFalse($changed);
    }

    public function testItDetectsNanDoesNotChange(): void
    {
        $detector = ConstantChangeDetector::fromName('NAN');

        $changed = $detector->changed();

        self::assertFalse($changed);
    }

    public function testItDetectsSomeFloatNanChanged(): void
    {
        $detector = new ConstantChangeDetector(name: 'NAN', exists: true, value: 0.5);

        $changed = $detector->changed();

        self::assertTrue($changed);
    }

    public function testItDetectsNonFloatNanChanged(): void
    {
        $detector = new ConstantChangeDetector(name: 'NAN', exists: true, value: true);

        $changed = $detector->changed();

        self::assertTrue($changed);
    }

    public function testInf(): void
    {
        $detector = ConstantChangeDetector::fromName('INF');

        $changed = $detector->changed();

        self::assertFalse($changed);
    }

    public function testItReturnsDeduplicatedDetectors(): void
    {
        $detector = ChangeDetectors::from([
            new ConstantChangeDetector('A', true, 'a'),
            new ConstantChangeDetector('A', true, 'b'),
            new ConstantChangeDetector('A', false, 'a'),
            new ConstantChangeDetector('A', false, 'b'),
        ]);

        $deduplicated = $detector->deduplicate();

        self::assertCount(4, $deduplicated);
    }

    #[TestWith([
        new ConstantChangeDetector('A', true, new \stdClass()),
        'Typhoon\ChangeDetector\ConstantChangeDetector.A.1.O:8:"stdClass":0:{}',
    ])]
    #[TestWith([
        new ConstantChangeDetector('A', false, 'abc'),
        'Typhoon\ChangeDetector\ConstantChangeDetector.A.0.s:3:"abc";',
    ])]
    public function testDeduplicateResult(ConstantChangeDetector $detector, string $expectedHash): void
    {
        $deduplicate = $detector->deduplicate();

        self::assertSame([$expectedHash => $detector], $deduplicate);
    }
}
