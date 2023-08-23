<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class PhpDoc
{
    /**
     * @param array<non-empty-string, TypeNode> $paramTypes
     * @param array<non-empty-string, TemplateTagValueNode> $templates
     * @param list<GenericTypeNode> $extendedTypes
     * @param list<GenericTypeNode> $implementedTypes
     */
    public function __construct(
        public readonly ?TypeNode $varType = null,
        public readonly array $paramTypes = [],
        public readonly ?TypeNode $returnType = null,
        public readonly array $templates = [],
        public readonly array $extendedTypes = [],
        public readonly array $implementedTypes = [],
    ) {}
}
