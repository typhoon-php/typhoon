<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Reflector;

use ExtendedTypeSystem\Reflection\ChangeDetector;
use ExtendedTypeSystem\Reflection\Exporter\Exporter;
use ExtendedTypeSystem\Reflection\Reflection;
use XdgBaseDir\Xdg;

final class ReflectionCache
{
    /**
     * @var non-empty-string
     */
    private readonly string $directory;

    /**
     * @param ?non-empty-string $directory
     */
    public function __construct(
        ?string $directory = null,
        private readonly bool $detectChanges = true,
    ) {
        $this->directory = $directory ?? (new Xdg())->getHomeCacheDir() . '/php_extended_type_system_cache/' . md5(__DIR__);
    }

    /**
     * @param non-empty-string $file
     */
    public function hasFile(string $file): bool
    {
        if ($this->detectChanges) {
            return $this->include($this->file($file)) === true;
        }

        return file_exists($file);
    }

    /**
     * @param class-string<Reflection> $reflectionClass
     * @param non-empty-string $name
     */
    public function hasReflection(string $reflectionClass, string $name): bool
    {
        $file = $this->file($this->reflectionKey($reflectionClass, $name));

        if ($this->detectChanges) {
            return $this->include($file) instanceof $reflectionClass;
        }

        return file_exists($file);
    }

    /**
     * @template TReflection of Reflection
     * @param class-string<TReflection> $reflectionClass
     * @param non-empty-string $name
     * @return ?TReflection
     */
    public function getReflection(string $reflectionClass, string $name): ?Reflection
    {
        $reflection = $this->include($this->file($this->reflectionKey($reflectionClass, $name)));

        if ($reflection instanceof $reflectionClass) {
            /** @var TReflection */
            return $reflection;
        }

        return null;
    }

    /**
     * @param non-empty-string $name
     */
    public function setStandaloneReflection(string $name, Reflection $reflection, ChangeDetector $changeDetector): void
    {
        $reflectionFile = $this->file($this->reflectionKey($reflection::class, $name));
        $changeDetectorExported = Exporter::export($changeDetector);
        $reflectionExported = Exporter::export($reflection);
        $unlinkCode = $this->unlinkLine($reflectionFile);

        $this->write($reflectionFile, <<<PHP
            <?php
            
            if (\$this->detectChanges && ({$changeDetectorExported})->changed()) {
            {$unlinkCode}
                return null;
            }

            return {$reflectionExported};

            PHP);
    }

    /**
     * @param non-empty-string $file
     */
    public function setFileReflections(string $file, Reflections $reflections, ChangeDetector $changeDetector): void
    {
        $fileFile = $this->file($file);
        $unlinkCode = $this->unlinkLine($fileFile);
        $exportedReflectionsByFile = [];

        foreach ($reflections as $name => $reflection) {
            $reflectionFile = $this->file($this->reflectionKey($reflection::class, $name));
            $unlinkCode .= $this->unlinkLine($reflectionFile);
            $exportedReflectionsByFile[$reflectionFile] = Exporter::export($reflection);
        }

        $exportedChangeDetector = Exporter::export($changeDetector);
        $this->write($fileFile, <<<PHP
            <?php
            
            if (\$this->detectChanges && ({$exportedChangeDetector})->changed()) {
            {$unlinkCode}
                return false;
            }
            
            return true;

            PHP);

        foreach ($exportedReflectionsByFile as $reflectionFile => $exportedReflection) {
            $this->write($reflectionFile, <<<PHP
                <?php
                
                if (\$this->detectChanges && ({$exportedChangeDetector})->changed()) {
                {$unlinkCode}
                    return null;
                }

                return {$exportedReflection};

                PHP);
        }
    }

    public function clear(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
    }

    /**
     * @param class-string<Reflection> $reflectionClass
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function reflectionKey(string $reflectionClass, string $name): string
    {
        return $reflectionClass . '.' . $name;
    }

    /**
     * @return non-empty-string
     */
    private function file(string $key): string
    {
        $hash = md5($key);

        return sprintf('%s/%s/%s', $this->directory, substr($hash, 0, 2), substr($hash, 2));
    }

    /**
     * @param non-empty-string $file
     */
    private function write(string $file, string $code): void
    {
        $directory = \dirname($file);

        if (!is_dir($directory) && !mkdir($directory, recursive: true)) {
            throw new \RuntimeException();
        }

        @file_put_contents($file, $code) ?: throw new \RuntimeException();
    }

    /**
     * @param non-empty-string $file
     * @return non-empty-string
     */
    private function unlinkLine(string $file): string
    {
        return sprintf('    @unlink(%s);%s', var_export($file, true), PHP_EOL);
    }

    private function include(string $file): mixed
    {
        set_error_handler(static fn (int $level, string $message, string $file, int $line) => throw new \ErrorException(
            message: $message,
            severity: $level,
            filename: $file,
            line: $line,
        ));

        try {
            /** @psalm-suppress UnresolvableInclude */
            return include $file;
        } catch (\Throwable) {
            // log

            return null;
        } finally {
            restore_error_handler();
        }
    }
}
