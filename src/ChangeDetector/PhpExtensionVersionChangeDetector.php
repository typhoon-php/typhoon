<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

/**
 * @api
 */
final class PhpExtensionVersionChangeDetector implements ChangeDetector
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
        private readonly false|string $version,
    ) {}

    /**
     * @param non-empty-string $name
     */
    public static function fromName(string $name): self
    {
        $version = phpversion($name);

        if ($version === false && !\extension_loaded($name)) {
            throw new ExtensionIsNotInstalled($name);
        }

        return new self($name, $version);
    }

    public static function fromReflection(\ReflectionExtension $extension): self
    {
        /** @var non-empty-string */
        $name = $extension->name;

        return new self($name, $extension->getVersion() ?? false);
    }

    public function changed(): bool
    {
        return $this->version !== phpversion($this->name);
    }

    public function deduplicate(): array
    {
        return [self::class . json_encode(get_object_vars($this)) => $this];
    }
}
