<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Typhoon\Reflection\Exception\FileNotReadable;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
abstract class ChangeDetector
{
    /**
     * @param non-empty-string $file
     * @throws FileNotReadable
     */
    final public static function fromFileContents(string $file, ?string $contents = null): self
    {
        if ($contents === null) {
            $hash = md5_file($file);

            if ($hash === false) {
                throw new FileNotReadable($file);
            }
        } else {
            $hash = md5($contents);
        }

        return new FileChangeDetector($file, $hash);
    }

    /**
     * @throws FileNotReadable
     */
    final public static function fromReflection(\ReflectionClass $reflection): self
    {
        $file = $reflection->getFileName();

        if ($file !== false) {
            return self::fromFileContents($file);
        }

        $extensionName = $reflection->getExtensionName();

        if ($extensionName === false) {
            $extensionName = null;
        }

        if ($extensionName !== null || $reflection->isInternal()) {
            return new PhpVersionChangeDetector($extensionName, phpversion($extensionName));
        }

        return new NullChangeDetector();
    }

    abstract public function changed(): bool;
}
