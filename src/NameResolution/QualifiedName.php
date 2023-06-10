<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\NameResolution;

/**
 * @api
 * @psalm-immutable
 */
final class QualifiedName extends Name
{
    /**
     * @param non-empty-list<UnqualifiedName> $segments
     */
    public function __construct(
        protected readonly array $segments,
    ) {
        if (\count($segments) < 2) {
            throw new \InvalidArgumentException(sprintf('Qualified name expects at least 2 segments, got %d.', \count($segments)));
        }
    }

    public function firstSegment(): UnqualifiedName
    {
        return $this->segments[0];
    }

    public function withFirstSegmentReplaced(UnqualifiedName|self $name): self
    {
        /** @var self */
        return self::concatenate($name, ...\array_slice($this->segments, 1));
    }

    public function lastSegment(): UnqualifiedName
    {
        return $this->segments[array_key_last($this->segments)];
    }

    public function resolveInNamespace(null|UnqualifiedName|self $namespace = null): self
    {
        if ($namespace === null) {
            return $this;
        }

        /** @var self */
        return self::concatenate($namespace, $this);
    }

    public function toString(): string
    {
        return implode('\\', array_map(
            static fn (UnqualifiedName $name): string => $name->toString(),
            $this->segments,
        ));
    }
}
