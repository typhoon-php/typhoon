<?php

declare(strict_types=1);

namespace Typhoon\ChangeDetector;

/**
 * @api
 */
final class ConstantChangeDetector implements ChangeDetector
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $exists,
        private readonly mixed $value,
    ) {}

    /**
     * @param non-empty-string $name
     */
    public static function fromName(string $name): self
    {
        return new self(name: $name, exists: true, value: \constant($name));
    }

    public function changed(): bool
    {
        if (!$this->exists) {
            return \defined($this->name);
        }

        if ($this->name === 'NAN') {
            return !is_nan($this->value);
        }

        try {
            return \constant($this->name) !== $this->value;
        } catch (\Error) {
            return true;
        }
    }

    public function deduplicate(): array
    {
        return [self::class . json_encode(get_object_vars($this)) => $this];
    }
}
