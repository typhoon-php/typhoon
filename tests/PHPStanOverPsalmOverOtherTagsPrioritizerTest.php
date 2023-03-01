<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \ExtendedTypeSystem\PHPStanOverPsalmOverOtherTagsPrioritizer
 */
final class PHPStanOverPsalmOverOtherTagsPrioritizerTest extends TestCase
{
    public function testPhpStanTagHasHigherPriorityOverPsalmTag(): void
    {
        $prioritizer = new PHPStanOverPsalmOverOtherTagsPrioritizer();

        $phpStanPriority = $prioritizer->priorityFor('@phpstan-var');
        $psalmPriority = $prioritizer->priorityFor('@psalm-var');

        self::assertGreaterThan($psalmPriority, $phpStanPriority);
    }

    public function testPsalmTagHasHigherPriorityOverStandardTag(): void
    {
        $prioritizer = new PHPStanOverPsalmOverOtherTagsPrioritizer();

        $psalmPriority = $prioritizer->priorityFor('@psalm-var');
        $standardTagPriority = $prioritizer->priorityFor('@var');

        self::assertGreaterThan($standardTagPriority, $psalmPriority);
    }
}
