<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Typhoon\Reflection\Exception\DefaultReflectionException;

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
            $hash = md5_file($file);

            if ($hash === false) {
                throw new DefaultReflectionException(sprintf('File %s does not exist or is not readable.', $file));
            }
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

        $extension = $reflection->getExtension();

        if ($extension !== null) {
            $name = $extension->name;
            \assert($name !== '');

            return new PhpVersionChangeDetector($name, $extension->getVersion() ?? false);
        }

        if ($reflection->isInternal()) {
            return new PhpVersionChangeDetector(null, PHP_VERSION);
        }

        throw new DefaultReflectionException();
    }

    abstract public function changed(): bool;
}
