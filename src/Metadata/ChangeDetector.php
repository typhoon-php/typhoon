<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class ChangeDetector
{
    /**
     * @param non-empty-string $file
     */
    final public static function fromFileContents(string $file, ?string $contents = null): self
    {
        if ($contents === null) {
            $hash = exceptionally(static fn(): string|false => md5_file($file));
        } else {
            $hash = md5($contents);
        }

        return new FileChangeDetector($file, $hash);
    }

    final public static function fromReflection(\ReflectionClass $reflection): self
    {
        $file = $reflection->getFileName();

        if ($file !== false) {
            return self::fromFileContents($file);
        }

        $extension = $reflection->getExtensionName();

        return new PhpVersionChangeDetector(
            $extension,
            exceptionally(static fn(): string|false => phpversion($extension)),
        );
    }

    abstract public function changed(): bool;
}
