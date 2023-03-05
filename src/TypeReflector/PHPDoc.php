<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\TypeReflector
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
        $dollarName = '$'.$name;

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
        $templateNames = [];

        foreach ($this->templates() as $tagValue) {
            /** @var non-empty-string */
            $templateNames[] = $tagValue->name;
        }

        return $templateNames;
    }

    /**
     * @return \Generator<string, TemplateTagValueNode>
     */
    public function templates(): \Generator
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof TemplateTagValueNode) {
                yield $tag->name => $tag->value;
            }
        }
    }
}
