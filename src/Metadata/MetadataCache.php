<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class MetadataCache
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly bool $detectChanges,
    ) {}

    /**
     * @template TMetadata of RootMetadata
     * @param class-string<TMetadata> $class
     * @param non-empty-string $name
     * @return ?TMetadata
     */
    public function get(string $class, string $name): ?object
    {
        /** @psalm-suppress MixedAssignment */
        $metadata = $this->cache->get($this->key($class, $name));

        if (!$metadata instanceof $class) {
            return null;
        }

        /** @var TMetadata $metadata */
        if ($this->detectChanges && $metadata->changed()) {
            return null;
        }

        return $metadata;
    }

    /**
     * @param iterable<RootMetadata> $metadata
     */
    public function setMultiple(iterable $metadata): void
    {
        $metadataByKey = [];

        foreach ($metadata as $item) {
            $metadataByKey[$this->key($item::class, $item->name)] = $item;
        }

        if ($metadataByKey === []) {
            return;
        }

        $this->cache->setMultiple($metadataByKey);
    }

    /**
     * @param class-string<RootMetadata> $class
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function key(string $class, string $name): string
    {
        return hash('xxh128', $class . '#' . $name);
    }
}
