<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\DeprecatedTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\UsesTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\PhpDoc
 */
final class PhpDoc
{
    public function __construct(
        public readonly PhpDocNode $node,
    ) {}

    public function deprecatedMessage(): ?string
    {
        foreach ($this->tags() as $tag) {
            if ($tag->value instanceof DeprecatedTagValueNode) {
                return $tag->value->description;
            }
        }

        return null;
    }

    public function hasFinal(): bool
    {
        foreach ($this->tags() as $tag) {
            if ($tag->name === '@final') {
                return true;
            }
        }

        return false;
    }

    public function hasReadonly(): bool
    {
        foreach ($this->tags() as $tag) {
            if (\in_array($tag->name, ['@readonly', '@psalm-readonly', '@phpstan-readonly'], true)) {
                return true;
            }
        }

        return false;
    }

    public function varType(): ?TypeNode
    {
        $varTag = null;

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof VarTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<VarTagValueNode> $tag */
            if ($this->shouldReplaceTag($varTag, $tag)) {
                $varTag = $tag;
            }
        }

        return $varTag?->value->type;
    }

    /**
     * @return array<non-empty-string, TypeNode>
     */
    public function paramTypes(): array
    {
        $paramTags = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof ParamTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ParamTagValueNode> $tag */
            $name = $tag->value->parameterName;
            \assert(($name[0] ?? '') === '$');
            $name = substr($name, 1);
            \assert($name !== '');

            if ($this->shouldReplaceTag($paramTags[$name] ?? null, $tag)) {
                $paramTags[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): TypeNode => $tag->value->type,
            $paramTags,
        );
    }

    public function returnType(): ?TypeNode
    {
        $returnTag = null;

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof ReturnTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ReturnTagValueNode> $tag */
            if ($this->shouldReplaceTag($returnTag, $tag)) {
                $returnTag = $tag;
            }
        }

        return $returnTag?->value->type;
    }

    /**
     * @return list<TypeNode>
     */
    public function throwsTypes(): array
    {
        $throwsTypes = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof ThrowsTagValueNode) {
                continue;
            }

            $throwsTypes[] = $tag->value->type;
        }

        return $throwsTypes;
    }

    /**
     * @return list<PhpDocTagNode<TemplateTagValueNode>>
     */
    public function templateTags(): array
    {
        $templateTags = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof TemplateTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<TemplateTagValueNode> $tag */
            if ($this->shouldReplaceTag($templateTags[$tag->value->name] ?? null, $tag)) {
                $templateTags[$tag->value->name] = $tag;
            }
        }

        return array_values($templateTags);
    }

    /**
     * @return list<PhpDocTagNode<PropertyTagValueNode>>
     */
    public function propertyTags(): array
    {
        $propertyTags = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof PropertyTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<PropertyTagValueNode> $tag */
            if ($this->shouldReplaceTag($propertyTags[$tag->value->propertyName] ?? null, $tag)) {
                $propertyTags[$tag->value->propertyName] = $tag;
            }
        }

        return array_values($propertyTags);
    }

    /**
     * @return list<PhpDocTagNode<MethodTagValueNode>>
     */
    public function methodTags(): array
    {
        $methodTags = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof MethodTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<MethodTagValueNode> $tag */
            if ($this->shouldReplaceTag($methodTags[$tag->value->methodName] ?? null, $tag)) {
                $methodTags[$tag->value->methodName] = $tag;
            }
        }

        return array_values($methodTags);
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function extendedTypes(): array
    {
        $extendsTags = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof ExtendsTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ExtendsTagValueNode> $tag */
            $name = $tag->value->type->type->name;

            if ($this->shouldReplaceTag($extendsTags[$name] ?? null, $tag)) {
                $extendsTags[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
            array_values($extendsTags),
        );
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function implementedTypes(): array
    {
        $implementsTags = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof ImplementsTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<ImplementsTagValueNode> $tag */
            $name = $tag->value->type->type->name;

            if ($this->shouldReplaceTag($implementsTags[$name] ?? null, $tag)) {
                $implementsTags[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
            array_values($implementsTags),
        );
    }

    /**
     * @return list<GenericTypeNode>
     */
    public function usedTypes(): array
    {
        $tagsByName = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof UsesTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<UsesTagValueNode> $tag */
            $name = $tag->value->type->type->name;

            if ($this->shouldReplaceTag($tagsByName[$name] ?? null, $tag)) {
                $tagsByName[$name] = $tag;
            }
        }

        return array_map(
            static fn(PhpDocTagNode $tag): GenericTypeNode => $tag->value->type,
            array_values($tagsByName),
        );
    }

    /**
     * @return list<PhpDocTagNode<TypeAliasTagValueNode>>
     */
    public function typeAliasTags(): array
    {
        $tagsByAlias = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof TypeAliasTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<TypeAliasTagValueNode> $tag */
            if ($this->shouldReplaceTag($tagsByAlias[$tag->value->alias] ?? null, $tag)) {
                $tagsByAlias[$tag->value->alias] = $tag;
            }
        }

        return array_values($tagsByAlias);
    }

    /**
     * @return list<PhpDocTagNode<TypeAliasImportTagValueNode>>
     */
    public function typeAliasImportTags(): array
    {
        $tagsByAlias = [];

        foreach ($this->tags() as $tag) {
            if (!$tag->value instanceof TypeAliasImportTagValueNode) {
                continue;
            }

            /** @var PhpDocTagNode<TypeAliasImportTagValueNode> $tag */
            $alias = $tag->value->importedAs ?? $tag->value->importedAlias;

            if ($this->shouldReplaceTag($tagsByAlias[$alias] ?? null, $tag)) {
                $tagsByAlias[$alias] = $tag;
            }
        }

        return array_values($tagsByAlias);
    }

    /**
     * @return \Generator<PhpDocTagNode>
     */
    private function tags(): \Generator
    {
        foreach ($this->node->children as $child) {
            if ($child instanceof PhpDocTagNode) {
                yield $child;
            }
        }
    }

    private function shouldReplaceTag(?PhpDocTagNode $currentTag, PhpDocTagNode $newTag): bool
    {
        return $currentTag === null || PhpDocParser::priority($newTag) >= PhpDocParser::priority($currentTag);
    }
}
