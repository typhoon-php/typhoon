<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Source::class)]
final class SourceTest extends TestCase
{
    public function testItThrowsIfFileDoesNotExist(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Failed to open file a.txt.'));

        Source::fromFile('a.txt');
    }

    public function testItCorrectlyCreatesFromFile(): void
    {
        $expected = new Source(file_get_contents(__FILE__), __FILE__);

        $actual = Source::fromFile(__FILE__);

        self::assertEquals($expected, $actual);
    }

    public function testItCompilesDescriptionFromFileAndVia(): void
    {
        $source = Source::fromFile(__FILE__, 'autoloader');

        self::assertSame(sprintf('%s (via autoloader)', __FILE__), $source->description);
    }
}
