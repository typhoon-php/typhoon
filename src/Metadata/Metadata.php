<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Metadata;

use ExtendedTypeSystem\Parser\PHPDocTags;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\TemplateT;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
abstract class Metadata
{
    public function __construct(
        public readonly PHPDocTags $phpDocTags,
    ) {
    }

    abstract public function resolveName(string $name): string|StaticT;

    /**
     * @param non-empty-string $name
     */
    abstract public function tryReflectTemplateT(string $name): ?TemplateT;
}
