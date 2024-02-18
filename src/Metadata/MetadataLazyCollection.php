<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements \IteratorAggregate<int, RootMetadata>
 */
final class MetadataLazyCollection implements \IteratorAggregate
{
    /**
     * @var array<class-string<RootMetadata>, array<non-empty-string, RootMetadata|\Closure(): RootMetadata>>
     */
    private array $metadata = [];

    /**
     * @param class-string<RootMetadata> $class
     * @param non-empty-string $name
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

    public function set(RootMetadata $metadata): void
    {
        $this->metadata[$metadata::class][$metadata->name] = $metadata;
    }

    /**
     * @template TMetadata of RootMetadata
     * @param class-string<TMetadata> $class
     * @param non-empty-string $name
     * @param \Closure(): TMetadata $factory
     */
    public function setFactory(string $class, string $name, \Closure $factory): void
    {
        $this->metadata[$class][$name] = $factory;
    }

    public function clear(): void
    {
        $this->metadata = [];
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->metadata as &$byName) {
            foreach ($byName as &$metadata) {
                if ($metadata instanceof \Closure) {
                    yield $metadata = $metadata();
                } else {
                    yield $metadata;
                }
            }
        }
    }
}
