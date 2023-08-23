<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exporter\Exporter;
use XdgBaseDir\Xdg;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class FileReflectionCache implements ReflectionCache
{
    private readonly string $directory;

    public function __construct(
        ?string $directory = null,
        private readonly bool $detectChanges = true,
    ) {
        $this->directory = $directory ?? self::defaultCacheDirectory();
    }

    private static function defaultCacheDirectory(): string
    {
        return (new Xdg())->getHomeCacheDir() . '/php_typhoon_reflection/' . md5(__DIR__);
    }

    public function hasFile(string $file): bool
    {
        $file = $this->file($file);

        if ($this->detectChanges) {
            return $this->include($file) === true;
        }

        return file_exists($file);
    }

    public function hasReflection(string $reflectionClass, string $name): bool
    {
        $file = $this->file($this->reflectionKey($reflectionClass, $name));

        if ($this->detectChanges) {
            return $this->include($file) instanceof $reflectionClass;
        }

        return file_exists($file);
    }

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $reflectionClass
     * @param non-empty-string $name
     * @return ?TReflection
     */
    public function getReflection(string $reflectionClass, string $name): ?RootReflection
    {
        $reflection = $this->include($this->file($this->reflectionKey($reflectionClass, $name)));

        if ($reflection instanceof $reflectionClass) {
            /** @var TReflection */
            return $reflection;
        }

        return null;
    }

    public function setStandaloneReflection(RootReflection $reflection): void
    {
        $reflectionFile = $this->file($this->reflectionKey($reflection::class, $reflection->getName()));
        $reflectionExported = Exporter::export($reflection);
        $unlinkCode = $this->unlinkLine($reflectionFile);

        $this->write($reflectionFile, <<<PHP
            <?php
            
            \$reflection = {$reflectionExported};
            
            if (\$this->detectChanges && \$reflection->getChangeDetector()->changed()) {
            {$unlinkCode}
                return null;
            }

            return \$reflection;

            PHP);
    }

    public function setFileReflections(string $file, Reflections $reflections): void
    {
        $fileFile = $this->file($file);
        $unlinkCode = $this->unlinkLine($fileFile);
        $exportedReflectionsByFile = [];
        $changeDetector = null;

        foreach ($reflections as $name => $reflection) {
            $reflectionFile = $this->file($this->reflectionKey($reflection::class, $name));
            $unlinkCode .= $this->unlinkLine($reflectionFile);
            $exportedReflectionsByFile[$reflectionFile] = Exporter::export($reflection);
            $changeDetector ??= $reflection->getChangeDetector();
        }

        $exportedChangeDetector = Exporter::export($changeDetector ?? FileChangeDetector::fromFile($file));
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

                \$reflection = {$exportedReflection};
                
                if (\$this->detectChanges && \$reflection->getChangeDetector()->changed()) {
                {$unlinkCode}
                    return null;
                }

                return \$reflection;

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
     * @param class-string<RootReflection> $reflectionClass
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function reflectionKey(string $reflectionClass, string $name): string
    {
        return $reflectionClass . '.' . $name;
    }

    /**
     * @param non-empty-string $key
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
            throw new \RuntimeException('');
        }

        exceptionally(static fn (): int|false => file_put_contents($file, $code));
    }

    /**
     * @param non-empty-string $file
     * @return non-empty-string
     */
    private function unlinkLine(string $file): string
    {
        return sprintf('    @unlink(%s);%s', var_export($file, true), PHP_EOL);
    }

    /**
     * @param non-empty-string $file
     */
    private function include(string $file): mixed
    {
        set_error_handler(static fn (): bool => true);

        try {
            return include $file;
        } finally {
            restore_error_handler();
        }
    }
}
