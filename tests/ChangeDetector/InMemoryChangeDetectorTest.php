<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryChangeDetector::class)]
final class InMemoryChangeDetectorTest extends TestCase
{
    public function testItIsNotChangedInTheSameProcess(): void
    {
        $detector = new InMemoryChangeDetector();

        $changed = $detector->changed();

        self::assertFalse($changed);
    }

    public function testItIsChangedAfterSerialization(): void
    {
        /** @var InMemoryChangeDetector */
        $detector = unserialize(serialize(new InMemoryChangeDetector()));

        $changed = $detector->changed();

        self::assertTrue($changed);
    }

    public function testItIsDeduplicatedByState(): void
    {
        $unserialized = static fn(): InMemoryChangeDetector =>
            /** @var InMemoryChangeDetector */
            unserialize(serialize(new InMemoryChangeDetector()));
        $detector = ChangeDetectors::from([
            new InMemoryChangeDetector(),
            $unserialized(),
            new InMemoryChangeDetector(),
            $unserialized(),
            new InMemoryChangeDetector(),
            $unserialized(),
        ]);

        $deduplicated = $detector->deduplicate();

        self::assertCount(2, $deduplicated);
        self::assertFalse($deduplicated[array_key_first($deduplicated)]->changed());
        self::assertTrue($deduplicated[array_key_last($deduplicated)]->changed());
    }

    public function testDeduplicateResult(): void
    {
        $detector = new InMemoryChangeDetector();

        $deduplicate = $detector->deduplicate();

        self::assertSame(['Typhoon\ChangeDetector\InMemoryChangeDetector.0' => $detector], $deduplicate);
    }

    public function testUnserializedDeduplicateResult(): void
    {
        $detector = unserialize(serialize(new InMemoryChangeDetector()));
        \assert($detector instanceof InMemoryChangeDetector);

        $deduplicate = $detector->deduplicate();

        self::assertSame(['Typhoon\ChangeDetector\InMemoryChangeDetector.1' => $detector], $deduplicate);
    }
}
