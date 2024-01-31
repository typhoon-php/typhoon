<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class UnqualifiedName extends Name
{
    /**
     * @var non-empty-string
     */
    private readonly string $name;

    public function __construct(string $name)
    {
        if (preg_match('/^[a-zA-Z_\x80-\xff][\w\x80-\xff]*$/', $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid PHP label.', $name));
        }

        /** @var non-empty-string */
        $this->name = $name;
    }

    public function lastSegment(): self
    {
        return $this;
    }

    public function resolveInNamespace(null|self|QualifiedName $namespace = null): self|QualifiedName
    {
        if ($namespace === null) {
            return $this;
        }

        return self::concatenate($namespace, $this);
    }

    public function toString(): string
    {
        return $this->name;
    }
}
