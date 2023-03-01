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
final class PropertyMetadata extends Metadata
{
    public function __construct(
        private readonly bool $static,
        public readonly bool $promoted,
        private readonly ClassMetadata $class,
        PHPDocTags $phpDocTags,
    ) {
        parent::__construct($phpDocTags);
    }

    public function resolveName(string $name): string|StaticT
    {
        return $this->class->resolveName($name);
    }

    public function tryReflectTemplateT(string $name): ?TemplateT
    {
        if ($this->static) {
            return null;
        }

        return $this->class->tryReflectTemplateT($name);
    }
}
