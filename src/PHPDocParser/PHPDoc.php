<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\PHPDocParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class PHPDoc
{
    /**
     * @param list<PhpDocTagNode> $tags
     */
    public function __construct(
        private readonly array $tags = [],
    ) {
    }

    public function varType(): ?TypeNode
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof VarTagValueNode) {
                return $tag->value->type;
            }
        }

        return null;
    }

    public function paramType(string $name): ?TypeNode
    {
        $dollarName = '$' . $name;

        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ParamTagValueNode && $tag->value->parameterName === $dollarName) {
                return $tag->value->type;
            }
        }

        return null;
    }

    public function returnType(): ?TypeNode
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ReturnTagValueNode) {
                return $tag->value->type;
            }
        }

        return null;
    }

    /**
     * @return list<non-empty-string>
     */
    public function templateNames(): array
    {
        return array_column(iterator_to_array($this->templates(), preserve_keys: false), 'name');
    }

    /**
     * @return \Generator<non-empty-string, TemplateTagValueNode>
     */
    public function templates(): \Generator
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof TemplateTagValueNode) {
                yield $tag->name => $tag->value;
            }
        }
    }

    /**
     * @return \Generator<int, GenericTypeNode>
     */
    public function inheritedTypes(): \Generator
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof ExtendsTagValueNode || $tag->value instanceof ImplementsTagValueNode) {
                yield $tag->value->type;
            }
        }
    }
}
