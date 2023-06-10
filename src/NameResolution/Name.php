<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\NameResolution;

/**
 * @api
 * @psalm-immutable
 * @psalm-inheritors UnqualifiedName|QualifiedName|RelativeName|FullyQualifiedName
 */
abstract class Name
{
    /**
     * @psalm-pure
     * @return ($name is null ? null : self)
     */
    final public static function fromString(?string $name): ?self
    {
        if ($name === null) {
            return null;
        }

        $segments = explode('\\', $name);

        if ($segments[0] === '') {
            return new FullyQualifiedName(self::concatenate(...array_map(
                static fn (string $segment): UnqualifiedName => new UnqualifiedName($segment),
                \array_slice($segments, 1),
            )));
        }

        if ($segments[0] === 'namespace') {
            return new RelativeName(self::concatenate(...array_map(
                static fn (string $segment): UnqualifiedName => new UnqualifiedName($segment),
                \array_slice($segments, 1),
            )));
        }

        return self::concatenate(...array_map(
            static fn (string $segment): UnqualifiedName => new UnqualifiedName($segment),
            $segments,
        ));
    }

    /**
     * @psalm-pure
     */
    final public static function concatenate(null|UnqualifiedName|QualifiedName ...$segments): UnqualifiedName|QualifiedName
    {
        /** @var list<UnqualifiedName> */
        $resolvedSegments = [];

        foreach ($segments as $segment) {
            if ($segment === null) {
                continue;
            }

            if ($segment instanceof UnqualifiedName) {
                $resolvedSegments[] = $segment;

                continue;
            }

            $resolvedSegments = [...$resolvedSegments, ...$segment->segments];
        }

        if (\count($resolvedSegments) === 0) {
            throw new \InvalidArgumentException('Nothing to concatenate.');
        }

        if (\count($resolvedSegments) === 1) {
            return $resolvedSegments[0];
        }

        return new QualifiedName($resolvedSegments);
    }

    abstract public function lastSegment(): UnqualifiedName;

    abstract public function resolveInNamespace(null|UnqualifiedName|QualifiedName $namespace = null): UnqualifiedName|QualifiedName;

    /**
     * @return non-empty-string
     */
    abstract public function toString(): string;
}
