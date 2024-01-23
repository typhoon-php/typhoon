<?php

declare(strict_types=1);

namespace StaticAnalysisTester;

use Composer\InstalledVersions;
use PHPUnit\Framework\Assert;

/**
 * @api
 */
final class PsalmTester
{
    private function __construct(
        private string $psalmPath,
        private string $defaultArguments,
        private string $temporaryDirectory,
    ) {}

    public static function create(
        ?string $psalmPath = null,
        string $defaultArguments = '--no-progress --no-diff --config=' . __DIR__ . '/psalm.xml',
        ?string $temporaryDirectory = null,
    ): self {
        if ($psalmPath === null) {
            $installationPath = InstalledVersions::getInstallPath('vimeo/psalm') ?? throw new \RuntimeException('Psalm is not installed. Please, install or provide path to Psalm bin.');
            $psalmPath = $installationPath . '/psalm';
        }

        $temporaryDirectory ??= sys_get_temp_dir() . '/psalm_test';

        if (!is_dir($temporaryDirectory) && !mkdir($temporaryDirectory, recursive: true)) {
            throw new \RuntimeException(sprintf('Failed to create temporary directory %s.', $temporaryDirectory));
        }

        return new self(
            psalmPath: $psalmPath,
            defaultArguments: $defaultArguments,
            temporaryDirectory: $temporaryDirectory,
        );
    }

    public function test(StaticAnalysisTest $test): void
    {
        $codeFile = $this->createTemporaryCodeFile($test->code);

        try {
            /** @psalm-suppress ForbiddenCode */
            $output = shell_exec(sprintf(
                '%s --output-format=json %s %s',
                $this->psalmPath,
                $test->arguments ?: $this->defaultArguments,
                $codeFile,
            ));

            if (!\is_string($output)) {
                throw new \RuntimeException();
            }

            $formattedOutput = $this->formatOutput($output, $test->codeFirstLine);

            Assert::assertThat($formattedOutput, $test->constraint);
        } finally {
            @unlink($codeFile);
        }
    }

    private function createTemporaryCodeFile(string $contents): string
    {
        $file = tempnam($this->temporaryDirectory, 'code_');

        if (!$file) {
            throw new \LogicException(sprintf('Failed to create temporary code file in %s.', $this->temporaryDirectory));
        }

        file_put_contents($file, $contents);

        return $file;
    }

    private function formatOutput(string $output, int $codeFirstLine): string
    {
        /** @var list<array{type: string, column_from: int, line_from: int, message: string, ...}> */
        $decoded = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        return implode("\n", array_map(
            static fn(array $error): string => sprintf(
                '%s on line %d: %s',
                $error['type'],
                $error['line_from'] + $codeFirstLine - 1,
                $error['message'],
            ),
            $decoded,
        ));
    }
}
