<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Composer\InstalledVersions;
use Typhoon\Reflection\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exporter\Exporter;
use Typhoon\Reflection\ReflectionException;
use XdgBaseDir\Xdg;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @infection-ignore-all
 */
final class FileReflectionCache implements ReflectionCache
{
    private static ?int $scriptStartTime = null;

    private static ?string $reference = null;

    private static ?bool $opcacheEnabled = null;

    private readonly string $directory;

    public function __construct(
        ?string $directory = null,
        private readonly bool $detectChanges = true,
    ) {
        self::scriptStartTime();
        $this->directory = $directory ?? self::defaultCacheDirectory();
    }

    private static function scriptStartTime(): int
    {
        return self::$scriptStartTime ??= $_SERVER['REQUEST_TIME'] ?? time();
    }

    private static function defaultCacheDirectory(): string
    {
        return (new Xdg())->getHomeCacheDir() . '/php_typhoon_reflection/' . md5(__DIR__);
    }

    private static function reference(): string
    {
        return self::$reference ??= InstalledVersions::getReference('typhoon/reflection') ?? throw new ReflectionException();
    }

    /**
     * @psalm-suppress UnusedMethod
     */
    private static function referenceChanged(string $reference): bool
    {
        return $reference !== self::reference();
    }

    /**
     * @psalm-suppress MixedArgument
     */
    private static function opcacheEnabled(): bool
    {
        return self::$opcacheEnabled ??= (\function_exists('opcache_invalidate')
            && filter_var(\ini_get('opcache.enable'), FILTER_VALIDATE_BOOL)
            && (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) || filter_var(\ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOL)));
    }

    public function hasFile(string $file): bool
    {
        $fileCacheFile = $this->fileCacheFile($file);

        if ($this->detectChanges) {
            return $this->include($fileCacheFile) === true;
        }

        return file_exists($fileCacheFile);
    }

    public function hasReflection(string $reflectionClass, string $name): bool
    {
        if ($this->detectChanges) {
            return $this->getReflection($reflectionClass, $name) !== null;
        }

        return file_exists($this->reflectionCacheFile($reflectionClass, $name));
    }

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $reflectionClass
     * @param non-empty-string $name
     * @return ?TReflection
     */
    public function getReflection(string $reflectionClass, string $name): ?RootReflection
    {
        /** @var ?TReflection */
        return $this->include($this->reflectionCacheFile($reflectionClass, $name));
    }

    public function setStandaloneReflection(RootReflection $reflection): void
    {
        $reflectionCacheFile = $this->reflectionCacheFile($reflection::class, $reflection->getName());
        $referenceExported = Exporter::export(self::reference(), 1);
        $reflectionExported = Exporter::export($reflection);

        $this->write($reflectionCacheFile, <<<PHP
            <?php

            {$this->unlinkFunctionCode([$reflectionCacheFile])}

            if (self::referenceChanged({$referenceExported})) {
                \$unlink();

                return null;
            }
            
            try {
                \$reflection = {$reflectionExported};
            } catch (\\Throwable \$exception) {
                \$unlink();

                throw \$exception;
            }
            
            if (\$this->detectChanges && \$reflection->getChangeDetector()->changed()) {
                \$unlink();

                return null;
            }

            return \$reflection;

            PHP);
    }

    public function setFileReflections(string $file, Reflections $reflections): void
    {
        $fileCacheFile = $this->fileCacheFile($file);
        $unlinkFiles = [$fileCacheFile];
        $exportedReflectionsByFile = [];
        $changeDetector = null;
        $referenceExported = Exporter::export(self::reference());

        foreach ($reflections as $name => $reflection) {
            $reflectionFile = $this->reflectionCacheFile($reflection::class, $name);
            $unlinkFiles[] = $reflectionFile;
            $exportedReflectionsByFile[$reflectionFile] = Exporter::export($reflection, 1);
            $changeDetector ??= $reflection->getChangeDetector();
        }

        $changeDetectorExported = Exporter::export($changeDetector ?? FileChangeDetector::fromFile($file));
        $unlinkFunctionCode = $this->unlinkFunctionCode($unlinkFiles);

        $this->write($fileCacheFile, <<<PHP
            <?php
            
            {$unlinkFunctionCode}

            if (self::referenceChanged({$referenceExported}) || \$this->detectChanges && ({$changeDetectorExported})->changed()) {
                \$unlink();

                return false;
            }
            
            return true;

            PHP);

        foreach ($exportedReflectionsByFile as $reflectionFile => $exportedReflection) {
            $this->write($reflectionFile, <<<PHP
                <?php

                {$unlinkFunctionCode}

                if (self::referenceChanged({$referenceExported})) {
                    \$unlink();

                    return null;
                }

                try {
                    \$reflection = {$exportedReflection};
                } catch (\\Throwable \$exception) {
                    \$unlink();

                    throw \$exception;
                }

                if (\$this->detectChanges && \$reflection->getChangeDetector()->changed()) {
                    \$unlink();

                    return null;
                }

                return \$reflection;

                PHP);
        }
    }

    public function clear(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());

                continue;
            }

            if (self::opcacheEnabled()) {
                opcache_invalidate($file->getPathname(), true);
            }

            @unlink($file->getPathname());
        }
    }

    /**
     * @param non-empty-string $file
     * @return non-empty-string
     */
    private function fileCacheFile(string $file): string
    {
        return $this->cacheFile(md5($file));
    }

    /**
     * @param class-string<RootReflection> $reflectionClass
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function reflectionCacheFile(string $reflectionClass, string $name): string
    {
        $hash = hash_init('md5');
        hash_update($hash, $reflectionClass);
        hash_update($hash, $name);

        return $this->cacheFile(hash_final($hash));
    }

    /**
     * @return non-empty-string
     */
    private function cacheFile(string $hash): string
    {
        return sprintf('%s/%s/%s.php', $this->directory, substr($hash, 0, 2), substr($hash, 2));
    }

    /**
     * @param non-empty-string $file
     */
    private function write(string $file, string $code): void
    {
        exceptionally(static function () use ($file, $code): void {
            $directory = \dirname($file);

            if (!is_dir($directory)) {
                mkdir($directory, recursive: true);
            }

            $tmp = $directory . '/' . uniqid(more_entropy: true);
            $handle = fopen($tmp, 'x');
            fwrite($handle, $code);
            fclose($handle);

            // set mtime in the past to enable OPcache compilation for this file
            touch($tmp, self::scriptStartTime() - 10);

            rename($tmp, $file);

            if (self::opcacheEnabled()) {
                opcache_invalidate($file, true);
                opcache_compile_file($file);
            }
        });
    }

    /**
     * @param non-empty-list<non-empty-string> $files
     * @return non-empty-string
     */
    private function unlinkFunctionCode(array $files): string
    {
        $opcacheInvalidations = implode(PHP_EOL, array_map(
            static fn (string $file): string => sprintf('        opcache_invalidate(%s);', Exporter::export($file)),
            $files,
        ));
        $unlinks = implode(PHP_EOL, array_map(
            static fn (string $file): string => sprintf('    unlink(%s);', Exporter::export($file)),
            $files,
        ));

        return <<<PHP
            \$unlink = static function (): void {
                if (self::opcacheEnabled()) {
            {$opcacheInvalidations}
                }
            
            {$unlinks}
            };
            PHP;
    }

    /**
     * @param non-empty-string $file
     */
    private function include(string $file): mixed
    {
        try {
            return exceptionally(fn (): mixed => include $file);
        } catch (\Throwable $exception) {
            if (str_contains($exception->getMessage(), 'No such file or directory')) {
                return null;
            }

            throw new ReflectionException(sprintf('Failed to load cache file %s.', $file), previous: $exception);
        }
    }
}
