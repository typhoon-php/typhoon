<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\Reflection\TagPrioritizer;
use Typhoon\Reflection\Variance;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpDocParser
 */
final class PhpDocBuilder
{
    /**
     * @var ?PhpDocTagNode<VarTagValueNode>
     */
    private ?PhpDocTagNode $varTag = null;

    /**
     * @var array<non-empty-string, PhpDocTagNode<ParamTagValueNode>>
     */
    private array $paramTags = [];

    /**
     * @var ?PhpDocTagNode<ReturnTagValueNode>
     */
    private ?PhpDocTagNode $returnTag = null;

    /**
     * @var array<non-empty-string, PhpDocTagNode<TemplateTagValueNode>>
     */
    private array $templateTags = [];

    /**
     * @var array<non-empty-string, PhpDocTagNode<ExtendsTagValueNode>>
     */
    private array $extendsTags = [];

    /**
     * @var array<non-empty-string, PhpDocTagNode<ImplementsTagValueNode>>
     */
    private array $implementsTags = [];

    public function __construct(
        private readonly TagPrioritizer $tagPrioritizer,
    ) {}

    /**
     * @param array<PhpDocTagNode> $tags
     */
    public function addTags(array $tags): self
    {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    public function build(): PhpDoc
    {
        return new PhpDoc(
            varType: $this->varTag?->value->type,
            paramTypes: array_map(
                static fn (PhpDocTagNode $tag): TypeNode => $tag->value->type,
                $this->paramTags,
            ),
            returnType: $this->returnTag?->value->type,
            templates: array_map(
                static function (PhpDocTagNode $tag): TemplateTagValueNode {
                    $tag->value->setAttribute('variance', match (true) {
                        str_ends_with($tag->name, 'covariant') => Variance::COVARIANT,
                        str_ends_with($tag->name, 'contravariant') => Variance::CONTRAVARIANT,
                        default => Variance::INVARIANT,
                    });

                    return $tag->value;
                },
                $this->templateTags,
            ),
            extendedTypes: array_map(
                static fn (PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
                array_values($this->extendsTags),
            ),
            implementedTypes: array_map(
                static fn (PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
                array_values($this->implementsTags),
            ),
        );
    }

    private function addTag(PhpDocTagNode $tag): void
    {
        if ($tag->value instanceof VarTagValueNode) {
            /** @var PhpDocTagNode<VarTagValueNode> $tag */
            $this->addVarTag($tag);

            return;
        }

        if ($tag->value instanceof ParamTagValueNode) {
            /** @var PhpDocTagNode<ParamTagValueNode> $tag */
            $this->addParamTag($tag);

            return;
        }

        if ($tag->value instanceof ReturnTagValueNode) {
            /** @var PhpDocTagNode<ReturnTagValueNode> $tag */
            $this->addReturnTag($tag);

            return;
        }

        if ($tag->value instanceof TemplateTagValueNode) {
            /** @var PhpDocTagNode<TemplateTagValueNode> $tag */
            $this->addTemplateTag($tag);

            return;
        }

        if ($tag->value instanceof ExtendsTagValueNode) {
            /** @var PhpDocTagNode<ExtendsTagValueNode> $tag */
            $this->addExtendsTag($tag);

            return;
        }

        if ($tag->value instanceof ImplementsTagValueNode) {
            /** @var PhpDocTagNode<ImplementsTagValueNode> $tag */
            $this->addImplementsTag($tag);

            return;
        }
    }

    /**
     * @param PhpDocTagNode<VarTagValueNode> $tag
     */
    private function addVarTag(PhpDocTagNode $tag): void
    {
        if ($this->shouldReplaceTag($this->varTag, $tag)) {
            $this->varTag = $tag;
        }
    }

    /**
     * @param PhpDocTagNode<ParamTagValueNode> $tag
     */
    private function addParamTag(PhpDocTagNode $tag): void
    {
        $name = $tag->value->parameterName;
        \assert(($name[0] ?? '') === '$');
        $name = substr($name, 1);
        \assert($name !== '');

        if ($this->shouldReplaceTag($this->paramTags[$name] ?? null, $tag)) {
            $this->paramTags[$name] = $tag;
        }
    }

    /**
     * @param PhpDocTagNode<ReturnTagValueNode> $tag
     */
    private function addReturnTag(PhpDocTagNode $tag): void
    {
        if ($this->shouldReplaceTag($this->returnTag, $tag)) {
            $this->returnTag = $tag;
        }
    }

    /**
     * @param PhpDocTagNode<TemplateTagValueNode> $tag
     */
    private function addTemplateTag(PhpDocTagNode $tag): void
    {
        if ($this->shouldReplaceTag($this->templateTags[$tag->value->name] ?? null, $tag)) {
            $this->templateTags[$tag->value->name] = $tag;
        }
    }

    /**
     * @param PhpDocTagNode<ExtendsTagValueNode> $tag
     */
    private function addExtendsTag(PhpDocTagNode $tag): void
    {
        $name = $tag->value->type->type->name;

        if ($this->shouldReplaceTag($this->extendsTags[$name] ?? null, $tag)) {
            $this->extendsTags[$name] = $tag;
        }
    }

    /**
     * @param PhpDocTagNode<ImplementsTagValueNode> $tag
     */
    private function addImplementsTag(PhpDocTagNode $tag): void
    {
        $name = $tag->value->type->type->name;

        if ($this->shouldReplaceTag($this->implementsTags[$name] ?? null, $tag)) {
            $this->implementsTags[$name] = $tag;
        }
    }

    /**
     * @template TCurrentValueNode of PhpDocTagValueNode
     * @template TNewValueNode of PhpDocTagValueNode
     * @param PhpDocTagNode<TCurrentValueNode> $currentTag
     * @param PhpDocTagNode<TNewValueNode> $newTag
     */
    private function shouldReplaceTag(?PhpDocTagNode $currentTag, PhpDocTagNode $newTag): bool
    {
        return $currentTag === null || $this->priorityOf($newTag) >= $this->priorityOf($currentTag);
    }

    /**
     * @template TValueNode of PhpDocTagValueNode
     * @param PhpDocTagNode<TValueNode> $tag
     */
    private function priorityOf(PhpDocTagNode $tag): int
    {
        $priority = $tag->getAttribute('priority');

        if (!\is_int($priority)) {
            $priority = $this->tagPrioritizer->priorityFor($tag->name);
            $tag->setAttribute('priority', $priority);
        }

        return $priority;
    }
}
