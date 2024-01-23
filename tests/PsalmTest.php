<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StaticAnalysisTester\PsalmTester;
use StaticAnalysisTester\StaticAnalysisTest;

final class PsalmTest extends TestCase
{
    private ?PsalmTester $psalmTester = null;

    /**
     * @template TType
     * @param Type<TType> $_type
     * @return TType
     */
    public static function extractType(Type $_type): mixed
    {
        /** @var TType */
        return null;
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function phptFiles(): \Generator
    {
        foreach (glob(__DIR__ . '/psalm/*.phpt') as $file) {
            yield basename($file) => [$file];
        }
    }

    #[DataProvider('phptFiles')]
    public function testPhptFiles(string $phptFile): void
    {
        $this->psalmTester ??= PsalmTester::create();
        $this->psalmTester->test(StaticAnalysisTest::fromPhptFile($phptFile));
    }
}
