<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Metadata;

use ExtendedTypeSystem\NameResolution\NameResolver;
use ExtendedTypeSystem\Parser\PHPDocTags;
use ExtendedTypeSystem\Type\AtClass;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\TemplateT;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class ClassMetadata extends Metadata
{
    /**
     * @var class-string|StaticT
     */
    private readonly string|StaticT $static;

    /**
     * @param class-string $class
     */
    public function __construct(
        public readonly string $class,
        private readonly ?string $parent,
        bool $final,
        private readonly NameResolver $nameResolver,
        PHPDocTags $phpDocTags,
    ) {
        $this->static = $final ? $class : new StaticT($class);
        parent::__construct($phpDocTags);
    }

    public function resolveName(string $name): string|StaticT
    {
        if ($name === 'self') {
            return $this->class;
        }

        if ($name === 'parent') {
            return $this->parent ?? throw new \LogicException(sprintf(
                'Failed to resolve parent type: scope class %s does not have a parent.',
                $this->class,
            ));
        }

        if ($name === 'static') {
            return $this->static;
        }

        return $this->nameResolver->resolveName($name);
    }

    public function tryReflectTemplateT(string $name): ?TemplateT
    {
        foreach ($this->phpDocTags->findTagValues(TemplateTagValueNode::class) as $tagValue) {
            if ($tagValue->name === $name) {
                return new TemplateT($name, new AtClass($this->class));
            }
        }

        return null;
    }
}
