<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class UnqualifiedName
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

    /**
     * @param array<non-empty-string, UnqualifiedName|QualifiedName> $importTable
     */
    public function resolve(null|self|QualifiedName $namespace = null, array $importTable = []): self|QualifiedName
    {
        if (isset($importTable[$this->name])) {
            return $importTable[$this->name];
        }

        if ($namespace === null) {
            return $this;
        }

        if ($namespace instanceof self) {
            return new QualifiedName([$namespace, $this]);
        }

        return new QualifiedName([...$namespace->segments, $this]);
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->name;
    }
}
