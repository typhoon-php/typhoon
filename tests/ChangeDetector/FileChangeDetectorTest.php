<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileChangeDetector::class)]
final class FileChangeDetectorTest extends TestCase
{
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();
    }

    public function testFromFileThrowsForNonExistingFile(): void
    {
        $this->expectExceptionObject(new FileIsNotReadable('a.txt'));

        FileChangeDetector::fromFile('a.txt');
    }

    public function testFromFileThrowsIfFileRemoved(): void
    {
        $file = $this->root->url() . '/test.txt';
        touch($file);
        /**
         * @psalm-suppress UnusedFunctionCall
         * trigger internal filemtime caching
         */
        filemtime($file);
        $this->root->removeChild('test.txt');

        $this->expectExceptionObject(new FileIsNotReadable($file));

        FileChangeDetector::fromFile($file);
    }

    public function testItConsidersTouchedFileNotChanged(): void
    {
        $file = $this->root->url() . '/test.txt';
        touch($file, time() - 100);
        $mtime = filemtime($file);
        $detector = FileChangeDetector::fromFile($file);

        touch($file);
        clearstatcache();
        $newMtime = filemtime($file);
        $changed = $detector->changed();

        self::assertNotSame($mtime, $newMtime);
        self::assertFalse($changed);
    }

    public function testItDetectsContentsChange(): void
    {
        $file = $this->root->url() . '/test.txt';
        touch($file, time() - 100);
        $mtime = filemtime($file);
        $detector = FileChangeDetector::fromFile($file);

        file_put_contents($file, 'y');
        clearstatcache();
        $newMtime = filemtime($file);
        $changed = $detector->changed();

        self::assertNotSame($mtime, $newMtime);
        self::assertTrue($changed);
    }

    public function testItReturnsDeduplicatedDetectors(): void
    {
        $detector = ChangeDetectors::from([
            new FileChangeDetector('test1', 1, 'a'),
            new FileChangeDetector('test2', 2, 'b'),
            new FileChangeDetector('test1', 3, 'c'),
        ]);

        $deduplicated = $detector->deduplicate();

        self::assertCount(3, $deduplicated);
    }

    /**
     * @param false|non-empty-string $xxh3
     */
    #[TestWith([false, 'awdawd'])]
    #[TestWith([123, false])]
    public function testCannotCreateWithInvalidMtimeXxh3Combinations(false|int $mtime, false|string $xxh3): void
    {
        $this->expectException(\AssertionError::class);

        new FileChangeDetector('a', $mtime, $xxh3);
    }
}
