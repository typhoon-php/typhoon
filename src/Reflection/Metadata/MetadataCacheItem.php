<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TMetadata of RootMetadata
 */
final class MetadataCacheItem
{
    /**
     * @param TMetadata|\Closure(): TMetadata $metadata
     */
    public function __construct(
        private RootMetadata|\Closure $metadata,
    ) {}

    /**
     * @return TMetadata
     */
    public function get(): RootMetadata
    {
        if ($this->metadata instanceof \Closure) {
            $this->metadata = ($this->metadata)();
        }

        return $this->metadata;
    }

    public function changed(): bool
    {
        return $this->get()->changeDetector->changed();
    }

    public function __serialize(): array
    {
        return ['metadata' => $this->get()];
    }
}
