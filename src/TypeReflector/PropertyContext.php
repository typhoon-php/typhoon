<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\TypeReflector
 */
final class PropertyContext extends Context
{
    /**
     * @var ?non-empty-string
     */
    private ?string $name = null;

    /**
     * @param list<PhpDocTagNode> $phpDocTags
     */
    public function __construct(
        private readonly ClassLikeContext $classLikeContext,
        array $phpDocTags,
        private readonly bool $static,
        private null|Identifier|Name|ComplexType $typeNode,
    ) {
        parent::__construct($phpDocTags);
    }

    public function self(): string
    {
        return $this->classLikeContext->self();
    }

    public function parent(): string
    {
        return $this->classLikeContext->parent();
    }

    public function resolveName(Name $name): Name
    {
        return $this->classLikeContext->resolveName($name);
    }

    public function tryResolveTemplateType(string $name): ?Type
    {
        if ($this->static) {
            return null;
        }

        return $this->classLikeContext->tryResolveTemplateType($name);
    }

    /**
     * @param non-empty-string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function finish(TypeResolver $typeResolver): Context
    {
        \assert($this->name !== null);

        $this->classLikeContext->addPropertyType(
            name: $this->name,
            type: $typeResolver->resolveTypeNode($this, $this->phpDocVarType() ?? $this->typeNode) ?? types::mixed,
        );

        return $this->classLikeContext;
    }

    private function phpDocVarType(): ?TypeNode
    {
        foreach ($this->phpDocTags as $tag) {
            if ($tag->value instanceof VarTagValueNode) {
                return $tag->value->type;
            }
        }

        return null;
    }
}
