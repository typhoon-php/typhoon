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
    private const BASE_CACHE_DIR = __DIR__ . '/file_reflection_cache';

    private string $cacheDir = self::BASE_CACHE_DIR;

    public static function setUpBeforeClass(): void
    {
        (new Filesystem())->remove(self::BASE_CACHE_DIR);
    }

    public static function tearDownAfterClass(): void
    {
        (new Filesystem())->remove(self::BASE_CACHE_DIR);
    }

    protected function setUp(): void
    {
        $this->cacheDir = self::BASE_CACHE_DIR . '/' . uniqid(more_entropy: true);
        (new Filesystem())->mkdir($this->cacheDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->cacheDir);
    }

    public function testItCachesStandaloneReflection(): void
    {
        $cache = new FileReflectionCache(directory: $this->cacheDir);
        $reflection = new RootReflectionStub('a', changed: false);
        $cache->setStandaloneReflection($reflection);

        $cachedReflection = $cache->getReflection(RootReflectionStub::class, 'a');

        self::assertEquals($reflection, $cachedReflection);
    }

    public function testItDetectsStandaloneReflectionChange(): void
    {
        $cache = new FileReflectionCache(directory: $this->cacheDir);
        $reflection = new RootReflectionStub('a', changed: true);
        $cache->setStandaloneReflection($reflection);

        $cachedReflection = $cache->getReflection(RootReflectionStub::class, 'a');

        self::assertNull($cachedReflection);
        $this->assertNoFilesInCacheDir();
    }

    public function testItDoesNotDetectStandaloneReflectionChangeIfChangeDetectionDisabled(): void
    {
        $cache = new FileReflectionCache(directory: $this->cacheDir, detectChanges: false);
        $reflection = new RootReflectionStub('a', changed: true);
        $cache->setStandaloneReflection($reflection);

        $cachedReflection = $cache->getReflection(RootReflectionStub::class, 'a');

        self::assertEquals($reflection, $cachedReflection);
    }

    public function testItCachesFileReflections(): void
    {
        $cache = new FileReflectionCache(directory: $this->cacheDir);
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

    public function testItDetectsFileReflectionsChangeViaHasFile(): void
    {
        $cache = new FileReflectionCache(directory: $this->cacheDir);
        $reflectionA = new RootReflectionStub('a', changed: true);
        $reflectionB = new RootReflectionStub('b', changed: true);
        $reflections = new Reflections();
        $reflections->set($reflectionA);
        $reflections->set($reflectionB);
        $cache->setFileReflections(__FILE__, $reflections);

        $hasFile = $cache->hasFile(__FILE__);

        self::assertFalse($hasFile);
        $this->assertNoFilesInCacheDir();
    }

    public function testItDetectsFileReflectionsChangeViaGetReflection(): void
    {
        $cache = new FileReflectionCache(directory: $this->cacheDir);
        $reflectionA = new RootReflectionStub('a', changed: true);
        $reflectionB = new RootReflectionStub('b', changed: true);
        $reflections = new Reflections();
        $reflections->set($reflectionA);
        $reflections->set($reflectionB);
        $cache->setFileReflections(__FILE__, $reflections);

        $cachedReflectionA = $cache->getReflection(RootReflectionStub::class, 'a');

        self::assertNull($cachedReflectionA);
        $this->assertNoFilesInCacheDir();
    }

    public function testItDetectsFileChange(): void
    {
        $filesystem = new Filesystem();
        $file = $this->cacheDir . '/x.txt';
        $filesystem->touch($file);
        $cache = new FileReflectionCache(directory: $this->cacheDir);

        $cache->setFileReflections($file, new Reflections());
        $filesystem->remove($file);
        $hasFile = $cache->hasFile($file);

        self::assertFalse($hasFile);
        $this->assertNoFilesInCacheDir();
    }

    private function assertNoFilesInCacheDir(): void
    {
        self::assertCount(0, (new Finder())->files()->in($this->cacheDir));
    }
}
