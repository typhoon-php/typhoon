<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrefixBasedTagPrioritizer::class)]
final class PrefixBasedTagPrioritizerTest extends TestCase
{
    public function testPsalmTagHasHigherPriorityOverPHPStanTag(): void
    {
        $prioritizer = new PrefixBasedTagPrioritizer();

        $psalmPriority = $prioritizer->priorityFor('@psalm-var');
        $phpStanPriority = $prioritizer->priorityFor('@phpstan-var');

        self::assertGreaterThan($phpStanPriority, $psalmPriority);
    }

    public function testPHPStanTagHasHigherPriorityOverStandardTag(): void
    {
        $prioritizer = new PrefixBasedTagPrioritizer();

        $standardTagPriority = $prioritizer->priorityFor('@var');
        $phpStanPriority = $prioritizer->priorityFor('@phpstan-var');

        self::assertGreaterThan($standardTagPriority, $phpStanPriority);
    }
}
