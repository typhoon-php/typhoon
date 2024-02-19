<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements \IteratorAggregate<int, RootMetadata>
 */
final class MetadataCollection implements \IteratorAggregate
{
    /**
     * @var array<class-string<RootMetadata>, array<non-empty-string, RootMetadata|\Closure(): RootMetadata>>
     */
    private array $metadata = [];

    /**
     * @param class-string<RootMetadata> $class
     */
    public function has(string $class, string $name): bool
    {
        return isset($this->metadata[$class][$name]);
    }

    /**
     * @template TMetadata of RootMetadata
     * @param class-string<TMetadata> $class
     * @param non-empty-string $name
     * @return ?TMetadata
     */
    public function get(string $class, string $name): ?object
    {
        /** @var null|TMetadata|\Closure(): TMetadata $metadata */
        $metadata = $this->metadata[$class][$name] ?? null;

        if ($metadata === null) {
            return null;
        }

        if ($metadata instanceof \Closure) {
            return $this->metadata[$class][$name] = $metadata();
        }

        return $metadata;
    }

    /**
     * @template TMetadata of RootMetadata
     * @param class-string<TMetadata> $class
     * @param non-empty-string $name
     * @param \Closure(): TMetadata $factory
     */
    public function set(string $class, string $name, \Closure $factory): void
    {
        $this->metadata[$class][$name] = $factory;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->metadata as $class => $byName) {
            foreach ($byName as $name => $metadata) {
                if ($metadata instanceof \Closure) {
                    yield $this->metadata[$class][$name] = $metadata();
                } else {
                    yield $metadata;
                }
            }
        }
    }
}
