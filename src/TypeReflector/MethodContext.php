<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\MethodTypeReflection;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\TypeReflector
 */
final class MethodContext extends Context
{
    /**
     * @var array<non-empty-string, array{null|Identifier|Name|ComplexType, bool}>
     */
    private array $parameterTypeNodes = [];

    /**
     * @param list<PhpDocTagNode> $phpDocTags
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly ClassLikeContext $classLikeContext,
        array $phpDocTags,
        private readonly string $name,
        private readonly bool $static,
        private readonly null|Identifier|Name|ComplexType $returnTypeNode,
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
        if ($this->hasTemplate($name)) {
            return types::methodTemplate($name, $this->self(), $this->name);
        }

        if ($this->static) {
            return null;
        }

        return $this->classLikeContext->tryResolveTemplateType($name);
    }

    /**
     * @param non-empty-string $name
     */
    public function addParameterType(string $name, null|Identifier|Name|ComplexType $typeNode, bool $promoted): void
    {
        $this->parameterTypeNodes[$name] = [$typeNode, $promoted];
    }

    public function finish(TypeResolver $typeResolver): Context
    {
        $parameterTypes = [];

        foreach ($this->parameterTypeNodes as $name => [$typeNode, $promoted]) {
            $parameterTypes[$name] = $typeResolver->resolveTypeNode($this, $this->phpDocParamType($name) ?? $typeNode) ?? types::mixed;

            if ($promoted) {
                $this->classLikeContext->addPropertyType($name, $parameterTypes[$name]);
            }
        }

        $this->classLikeContext->addMethodTypeReflection(
            new MethodTypeReflection(
                name: $this->name,
                templates: $this->buildTemplateReflections($typeResolver),
                parameterTypes: $parameterTypes,
                returnType: $typeResolver->resolveTypeNode($this, $this->phpDocReturnType() ?? $this->returnTypeNode) ?? types::mixed,
            ),
        );

        return $this->classLikeContext;
    }

    private function phpDocParamType(string $name): ?TypeNode
    {
        foreach ($this->phpDocTags as $tag) {
            if ($tag->value instanceof ParamTagValueNode && $tag->value->parameterName === '$'.$name) {
                return $tag->value->type;
            }
        }

        return null;
    }

    private function phpDocReturnType(): ?TypeNode
    {
        foreach ($this->phpDocTags as $tag) {
            if ($tag->value instanceof ReturnTagValueNode) {
                return $tag->value->type;
            }
        }

        return null;
    }
}
