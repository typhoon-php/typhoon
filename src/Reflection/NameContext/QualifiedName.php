<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class QualifiedName
{
    /**
     * @param non-empty-list<UnqualifiedName> $segments
     */
    public function __construct(
        public readonly array $segments,
    ) {
        if (\count($segments) < 2) {
            throw new InvalidName(sprintf('Qualified name expects at least 2 segments, got %d', \count($segments)));
        }
    }

    public function lastSegment(): UnqualifiedName
    {
        return $this->segments[\count($this->segments) - 1];
    }

    /**
     * @param array<non-empty-string, UnqualifiedName|QualifiedName> $importTable
     */
    public function resolve(null|UnqualifiedName|self $namespace = null, array $importTable = []): self
    {
        $firstSegment = $this->segments[0]->toString();
        $segmentsToAppend = $this->segments;

        if (isset($importTable[$firstSegment])) {
            $namespace = $importTable[$firstSegment];
            array_shift($segmentsToAppend);
        } elseif ($namespace === null) {
            return $this;
        }

        if ($namespace instanceof self) {
            return new self([...$namespace->segments, ...$segmentsToAppend]);
        }

        return new self([$namespace, ...$segmentsToAppend]);
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return implode('\\', array_map(
            static fn(UnqualifiedName $name): string => $name->toString(),
            $this->segments,
        ));
    }
}
