<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\TemplateReflection;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use ExtendedTypeSystem\Variance;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\TypeReflector
 */
abstract class Context
{
    /**
     * @var ?array<non-empty-string, true>
     */
    private ?array $templateNamesMap = null;

    /**
     * @param list<PhpDocTagNode> $phpDocTags
     */
    public function __construct(
        protected readonly array $phpDocTags,
    ) {
    }

    /**
     * @return class-string
     */
    abstract public function self(): string;

    /**
     * @return class-string
     */
    abstract public function parent(): string;

    abstract public function resolveName(Name $name): Name;

    /**
     * @param non-empty-string $name
     */
    abstract public function tryResolveTemplateType(string $name): ?Type;

    /**
     * @param non-empty-string $name
     */
    final protected function hasTemplate(string $name): bool
    {
        if ($this->templateNamesMap === null) {
            $this->templateNamesMap = [];

            foreach ($this->phpDocTags as $tag) {
                if ($tag->value instanceof TemplateTagValueNode) {
                    /** @var non-empty-string $tag->value->name */
                    $this->templateNamesMap[$tag->value->name] = true;
                }
            }
        }

        return isset($this->templateNamesMap[$name]);
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    final protected function buildTemplateReflections(TypeResolver $typeResolver): array
    {
        $templateReflections = [];
        $templateIndex = 0;

        foreach ($this->phpDocTags as $tag) {
            if ($tag->value instanceof TemplateTagValueNode) {
                /** @var non-empty-string $tag->value->name */
                $templateReflections[$tag->value->name] = new TemplateReflection(
                    index: $templateIndex++,
                    name: $tag->value->name,
                    constraint: $typeResolver->resolveTypeNode($this, $tag->value->bound) ?? types::mixed,
                    variance: match (true) {
                        str_ends_with($tag->name, 'covariant') => Variance::COVARIANT,
                        str_ends_with($tag->name, 'contravariant') => Variance::CONTRAVARIANT,
                        default => Variance::INVARIANT,
                    },
                );
            }
        }

        return $templateReflections;
    }
}
