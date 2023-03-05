<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \ExtendedTypeSystem\PHPStanOverPsalmOverOtherPHPDocTagPrioritizer
 */
final class PHPStanOverPsalmOverOtherPHPDocTagPrioritizerTest extends TestCase
{
    public function testPhpStanTagHasHigherPriorityOverPsalmTag(): void
    {
        $prioritizer = new PHPStanOverPsalmOverOtherPHPDocTagPrioritizer();

        $phpStanPriority = $prioritizer->priorityFor('@phpstan-var');
        $psalmPriority = $prioritizer->priorityFor('@psalm-var');

        self::assertGreaterThan($psalmPriority, $phpStanPriority);
    }

    public function testPsalmTagHasHigherPriorityOverStandardTag(): void
    {
        $prioritizer = new PHPStanOverPsalmOverOtherPHPDocTagPrioritizer();

        $psalmPriority = $prioritizer->priorityFor('@psalm-var');
        $standardTagPriority = $prioritizer->priorityFor('@var');

        self::assertGreaterThan($standardTagPriority, $psalmPriority);
    }
}
