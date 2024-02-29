<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class MetadataStorage
{
    /**
     * @var array<non-empty-string, MetadataCacheItem>
     */
    private array $deferred = [];

    public function __construct(
        private readonly ?CacheInterface $cache,
    ) {}

    /**
     * @param class-string<RootMetadata> $class
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private static function key(string $class, string $name): string
    {
        return hash('xxh128', $class . '#' . $name);
    }

    /**
     * @template TMetadata of RootMetadata
     * @param class-string<TMetadata> $class
     * @param non-empty-string $name
     * @return ?TMetadata
     */
    public function get(string $class, string $name): null|RootMetadata
    {
        $key = self::key($class, $name);

        if (isset($this->deferred[$key])) {
            /** @var TMetadata */
            return $this->deferred[$key]->get();
        }

        $metadata = $this->cache?->get(self::key($class, $name));

        if ($metadata instanceof MetadataCacheItem) {
            /** @var TMetadata */
            return $metadata->get();
        }

        return null;
    }

    public function save(RootMetadata $metadata): void
    {
        $this->cache?->set(self::key($metadata::class, $metadata->name), new MetadataCacheItem($metadata));
    }

    /**
     * @template TMetadata of RootMetadata
     * @param class-string<TMetadata> $class
     * @param non-empty-string $name
     * @param \Closure(): TMetadata $metadata
     */
    public function saveDeferred(string $class, string $name, \Closure $metadata): void
    {
        $this->deferred[self::key($class, $name)] = new MetadataCacheItem($metadata);
    }

    public function commit(): void
    {
        if ($this->deferred === []) {
            return;
        }

        $this->cache?->setMultiple($this->deferred);
        $this->deferred = [];
    }
}
