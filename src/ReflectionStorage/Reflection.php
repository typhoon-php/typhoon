<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ReflectionStorage;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\ReflectionStorage
 * @template TReflection of object
 */
final class Reflection
{
    public bool $cached = false;

    /**
     * @param TReflection|(\Closure(): TReflection) $reflection
     */
    public function __construct(
        private object $reflection,
        private readonly ChangeDetector $changeDetector,
    ) {}

    /**
     * @return TReflection
     */
    public function get(): object
    {
        if ($this->reflection instanceof \Closure) {
            /** @var TReflection */
            return $this->reflection = ($this->reflection)();
        }

        return $this->reflection;
    }

    public function changed(): bool
    {
        return $this->changeDetector->changed();
    }

    public function __serialize(): array
    {
        return [$this->get(), $this->changeDetector];
    }

    /**
     * @param array{TReflection, ChangeDetector} $data
     */
    public function __unserialize(array $data): void
    {
        [$this->reflection, $this->changeDetector] = $data;
    }
}
