<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Metadata;

use ExtendedTypeSystem\Parser\PHPDocTags;
use ExtendedTypeSystem\Type\AtMethod;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\TemplateT;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class MethodMetadata extends Metadata
{
    private readonly AtMethod $at;

    /**
     * @param non-empty-string $method
     */
    public function __construct(
        string $method,
        private readonly bool $static,
        private readonly ClassMetadata $class,
        PHPDocTags $phpDocTags,
    ) {
        $this->at = new AtMethod($class->class, $method);

        parent::__construct($phpDocTags);
    }

    public function resolveName(string $name): string|StaticT
    {
        return $this->class->resolveName($name);
    }

    public function tryReflectTemplateT(string $name): ?TemplateT
    {
        foreach ($this->phpDocTags->findTagValues(TemplateTagValueNode::class) as $tagValue) {
            if ($tagValue->name === $name) {
                return new TemplateT($name, $this->at);
            }
        }

        if ($this->static) {
            return null;
        }

        return $this->class->tryReflectTemplateT($name);
    }
}
