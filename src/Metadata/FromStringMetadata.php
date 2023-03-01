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
final class FromStringMetadata extends Metadata
{
    public function __construct(
        private readonly ?ClassMetadata $classMetadata,
        PHPDocTags $phpDocTags,
    ) {
        parent::__construct($phpDocTags);
    }

    public function resolveName(string $name): string|StaticT
    {
        return $this->classMetadata?->resolveName($name) ?? $name;
    }

    public function tryReflectTemplateT(string $name): ?TemplateT
    {
        return $this->classMetadata?->tryReflectTemplateT($name);
    }
}
