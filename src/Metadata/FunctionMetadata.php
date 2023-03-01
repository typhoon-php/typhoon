<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Metadata;

use ExtendedTypeSystem\NameResolution\NameResolver;
use ExtendedTypeSystem\Parser\PHPDocTags;
use ExtendedTypeSystem\Type\AtFunction;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\TemplateT;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class FunctionMetadata extends Metadata
{
    private readonly ?AtFunction $at;

    /**
     * @param ?callable-string $function
     */
    public function __construct(
        ?string $function,
        private readonly ?ClassMetadata $scopeClass,
        private readonly NameResolver $nameResolver,
        PHPDocTags $phpDocTags,
    ) {
        $this->at = $function === null ? null : new AtFunction($function);
        parent::__construct($phpDocTags);
    }

    public function resolveName(string $name): string|StaticT
    {
        if (\in_array($name, ['self', 'static', 'parent'], true)) {
            return $this->scopeClass()?->resolveName($name) ?? throw new \LogicException(sprintf(
                'Cannot resolve %s type outside of a class scope.',
                $name,
            ));
        }

        return $this->nameResolver->resolveName($name);
    }

    public function tryReflectTemplateT(string $name): ?TemplateT
    {
        if ($this->at === null) {
            return null;
        }

        foreach ($this->phpDocTags->findTagValues(TemplateTagValueNode::class) as $tagValue) {
            if ($tagValue->name === $name) {
                return new TemplateT($name, $this->at);
            }
        }

        return null;
    }

    public function scopeClass(): ?ClassMetadata
    {
        return $this->scopeClass;
    }
}
