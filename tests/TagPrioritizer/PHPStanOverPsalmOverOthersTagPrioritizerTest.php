<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TagPrioritizer;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \ExtendedTypeSystem\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer
 */
final class PHPStanOverPsalmOverOthersTagPrioritizerTest extends TestCase
{
    public function testPhpStanTagHasHigherPriorityOverPsalmTag(): void
    {
        $prioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer();

        $phpStanPriority = $prioritizer->priorityFor('@phpstan-var');
        $psalmPriority = $prioritizer->priorityFor('@psalm-var');

        self::assertGreaterThan($psalmPriority, $phpStanPriority);
    }

    public function testPsalmTagHasHigherPriorityOverStandardTag(): void
    {
        $prioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer();

        $psalmPriority = $prioritizer->priorityFor('@psalm-var');
        $standardTagPriority = $prioritizer->priorityFor('@var');

        self::assertGreaterThan($standardTagPriority, $psalmPriority);
    }
}
