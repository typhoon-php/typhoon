<?php

declare(strict_types=1);

namespace StaticAnalysisTester;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\Constraint\StringMatchesFormatDescription;

/**
 * @api
 * @psalm-type PhptSections = array<non-empty-string, array{string, positive-int}>
 */
final class StaticAnalysisTest
{
    /**
     * @param positive-int $codeFirstLine
     */
    public function __construct(
        public readonly string $code,
        public readonly Constraint $constraint,
        public readonly string $arguments = '',
        public readonly int $codeFirstLine = 1,
    ) {}

    /**
     * @see https://qa.php.net/phpt_details.php
     */
    public static function fromPhptFile(string $phptFile): self
    {
        $sections = self::parsePhpt($phptFile);

        if (!isset($sections['FILE'])) {
            throw new \LogicException(sprintf('File %s must have a FILE section.', $phptFile));
        }

        return new self(
            code: $sections['FILE'][0],
            constraint: self::resolvePhptConstraint($phptFile, $sections),
            arguments: $sections['ARGS'][0] ?? '',
            codeFirstLine: $sections['FILE'][1],
        );
    }

    /**
     * @param PhptSections $sections
     */
    private static function resolvePhptConstraint(string $file, array $sections): Constraint
    {
        if (isset($sections['EXPECT'])) {
            return new IsIdentical($sections['EXPECT'][0]);
        }

        if (isset($sections['EXPECTF'])) {
            return new StringMatchesFormatDescription($sections['EXPECTF'][0]);
        }

        if (isset($sections['EXPECTREGEX'])) {
            return new RegularExpression($sections['EXPECTREGEX'][0]);
        }

        if (isset($sections['EXPECT_EXTERNAL'])) {
            return new IsIdentical(file_get_contents($sections['EXPECT_EXTERNAL'][0]));
        }

        if (isset($sections['EXPECTF_EXTERNAL'])) {
            return new StringMatchesFormatDescription(file_get_contents($sections['EXPECTF_EXTERNAL'][0]));
        }

        if (isset($sections['EXPECTREGEX_EXTERNAL'])) {
            return new RegularExpression(file_get_contents($sections['EXPECTREGEX_EXTERNAL'][0]));
        }

        throw new \LogicException(sprintf('File %s must have an EXPECT* section.', $file));
    }

    /**
     * @return PhptSections
     */
    private static function parsePhpt(string $phptFile): array
    {
        $name = null;
        $sections = [];
        $lineNumber = 0;

        foreach (file($phptFile, FILE_IGNORE_NEW_LINES) as $line) {
            ++$lineNumber;

            if (preg_match('/^--([_A-Z]+)--/', $line, $matches)) {
                /** @var non-empty-string */
                $name = $matches[1];
                $sections[$name] = ['', $lineNumber + 1];

                continue;
            }

            if ($name === null) {
                throw new \LogicException('.phpt file must start with a section delimiter, f.e. --TEST--.');
            }

            $sections[$name][0] .= ($sections[$name][0] ? "\n" : '') . $line;
        }

        /** @var PhptSections */
        return $sections;
    }
}
