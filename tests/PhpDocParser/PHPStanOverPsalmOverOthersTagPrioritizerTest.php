<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PhpDocParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PHPStanOverPsalmOverOthersTagPrioritizer::class)]
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
