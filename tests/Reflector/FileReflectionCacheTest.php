<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[CoversClass(FileReflectionCache::class)]
final class FileReflectionCacheTest extends TestCase
{
    private const CACHE_DIR = __DIR__ . '/file_reflection_cache';

    protected function setUp(): void
    {
        (new Filesystem())->remove(self::CACHE_DIR);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(self::CACHE_DIR);
    }

    private static function assertCacheDirEmpty(): void
    {
        self::assertCount(0, (new Finder())->files()->in(self::CACHE_DIR));
    }

    public function testItCachesStandaloneReflection(): void
    {
        $cache = new FileReflectionCache(directory: self::CACHE_DIR);
        $reflection = new RootReflectionStub('a', changed: false);
        $cache->setStandaloneReflection($reflection);

        $cachedReflection = $cache->getReflection(RootReflectionStub::class, 'a');

        self::assertEquals($reflection, $cachedReflection);
    }

    public function testItDetectsStandaloneReflectionChange(): void
    {
        $cache = new FileReflectionCache(directory: self::CACHE_DIR);
        $reflection = new RootReflectionStub('a', changed: true);
        $cache->setStandaloneReflection($reflection);

        $cachedReflection = $cache->getReflection(RootReflectionStub::class, 'a');

        self::assertNull($cachedReflection);
        self::assertCacheDirEmpty();
    }

    public function testItDoesNotDetectStandaloneReflectionChangeIfChangeDetectionDisabled(): void
    {
        $cache = new FileReflectionCache(directory: self::CACHE_DIR, detectChanges: false);
        $reflection = new RootReflectionStub('a', changed: true);
        $cache->setStandaloneReflection($reflection);

        $cachedReflection = $cache->getReflection(RootReflectionStub::class, 'a');

        self::assertEquals($reflection, $cachedReflection);
    }

    public function testItCachesFileReflections(): void
    {
        $cache = new FileReflectionCache(directory: self::CACHE_DIR);
        $reflectionA = new RootReflectionStub('a', changed: false);
        $reflectionB = new RootReflectionStub('b', changed: false);
        $reflections = new Reflections();
        $reflections->set($reflectionA);
        $reflections->set($reflectionB);
        $cache->setFileReflections(__FILE__, $reflections);

        $hasFile = $cache->hasFile(__FILE__);
        $cachedReflectionA = $cache->getReflection(RootReflectionStub::class, 'a');
        $cachedReflectionB = $cache->getReflection(RootReflectionStub::class, 'b');

        self::assertTrue($hasFile);
        self::assertEquals($reflectionA, $cachedReflectionA);
        self::assertEquals($reflectionB, $cachedReflectionB);
    }

    public function testItDetectsFileReflectionsChange(): void
    {
        $cache = new FileReflectionCache(directory: self::CACHE_DIR);
        $reflectionA = new RootReflectionStub('a', changed: true);
        $reflectionB = new RootReflectionStub('b', changed: false);
        $reflections = new Reflections();
        $reflections->set($reflectionA);
        $reflections->set($reflectionB);
        $cache->setFileReflections(__FILE__, $reflections);

        $hasFile = $cache->hasFile(__FILE__);
        $cachedReflectionA = $cache->getReflection(RootReflectionStub::class, 'a');
        $cachedReflectionB = $cache->getReflection(RootReflectionStub::class, 'b');

        self::assertFalse($hasFile);
        self::assertNull($cachedReflectionA);
        self::assertNull($cachedReflectionB);
        self::assertCacheDirEmpty();
    }

    public function testItDetectsFileChange(): void
    {
        $file = self::CACHE_DIR . '/x.txt';
        $filesystem = new Filesystem();
        $filesystem->mkdir(self::CACHE_DIR);
        $filesystem->touch($file);
        $cache = new FileReflectionCache(directory: self::CACHE_DIR);

        $cache->setFileReflections($file, new Reflections());
        $filesystem->remove($file);
        $hasFile = $cache->hasFile($file);

        self::assertFalse($hasFile);
        self::assertCacheDirEmpty();
    }
}
