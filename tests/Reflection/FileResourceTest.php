<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Reflection\Exception\FileNotReadable;

#[CoversClass(FileResource::class)]
final class FileResourceTest extends TestCase
{
    public function testItThrowsOnEmptyFile(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('File must not be empty');

        new FileResource('');
    }

    public function testItThrowsOnEmptyExtension(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Extension must not be empty');

        new FileResource('file', '');
    }

    public function testItGetsContents(): void
    {
        vfsStream::setup(structure: ['test.txt' => 'test']);
        $file = new FileResource(vfsStream::url('root/test.txt'));

        $contents = $file->contents();

        self::assertSame('test', $contents);
    }

    public function testItThrowsIfNoFile(): void
    {
        $file = new FileResource('some');

        $this->expectException(FileNotReadable::class);
        $this->expectExceptionMessage('File "some" does not exist or is not readable');

        $file->contents();
    }

    public function testItMemoizesContents(): void
    {
        vfsStream::setup(structure: ['test.txt' => 'test']);
        $file = new FileResource(vfsStream::url('root/test.txt'));
        $file->contents();
        @unlink(vfsStream::url('root/test.txt'));

        $contents = $file->contents();

        self::assertSame('test', $contents);
    }

    public function testItIsNotInternalIfExtensionIsFalse(): void
    {
        $file = new FileResource('file');

        self::assertFalse($file->isInternal());
    }

    public function testItIsInternalIfExtensionIsSet(): void
    {
        $file = new FileResource('file', 'extension');

        self::assertTrue($file->isInternal());
    }
}
