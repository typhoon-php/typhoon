<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\PHPDocParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 * @psalm-immutable
 */
final class PHPDoc
{
    /**
     * @param array<non-empty-string, TypeNode> $paramTypes
     * @param array<non-empty-string, TemplateTagValueNode> $templates
     * @param list<GenericTypeNode> $inheritedTypes
     */
    public function __construct(
        public readonly ?TypeNode $varType = null,
        public readonly array $paramTypes = [],
        public readonly ?TypeNode $returnType = null,
        public readonly array $templates = [],
        public readonly array $inheritedTypes = [],
    ) {
    }
}
