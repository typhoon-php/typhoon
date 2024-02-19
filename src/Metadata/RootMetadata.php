<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Typhoon\Reflection\Cache\Changeable;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant TName of non-empty-string
 */
abstract class RootMetadata implements Changeable
{
    /**
     * @param TName $name
     */
    public function __construct(
        public readonly string $name,
        protected readonly ChangeDetector $changeDetector,
    ) {}

    final public function changed(): bool
    {
        return $this->changeDetector->changed();
    }
}
