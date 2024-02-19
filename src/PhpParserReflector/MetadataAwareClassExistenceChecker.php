<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataCollection;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class MetadataAwareClassExistenceChecker implements ClassExistenceChecker
{
    /**
     * @var \WeakReference<MetadataCollection>
     */
    private \WeakReference $metadata;

    public function __construct(
        MetadataCollection $metadata,
        private readonly ClassExistenceChecker $classExistenceChecker,
    ) {
        $this->metadata = \WeakReference::create($metadata);
    }

    public function classExists(string $name): bool
    {
        return $this->metadata->get()?->has(ClassMetadata::class, $name)
            ?? $this->classExistenceChecker->classExists($name);
    }
}
