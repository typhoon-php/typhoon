<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\ClassLikeTypeReflection;
use ExtendedTypeSystem\TypeReflector;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 * @template T of object
 */
final class ClassVisitor extends NodeVisitorAbstract
{
    /**
     * @var ?ClassLikeTypeReflection<T>
     * @psalm-readonly-allow-private-mutation
     */
    public ?ClassLikeTypeReflection $reflection = null;

    private ?Context $context = null;

    /**
     * @param class-string<T> $class
     */
    public function __construct(
        private readonly TypeReflector $typeReflector,
        private readonly NameContext $nameContext,
        private readonly TypeResolver $typeResolver,
        private readonly PHPDocParser $phpDocParser,
        private readonly string $class,
    ) {
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            /** @var class-string */
            $class = $node->namespacedName?->toString();

            if ($class !== $this->class) {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            if ($node instanceof Node\Stmt\Class_) {
                /** @var ?class-string */
                $parentClass = $node->extends?->toString();
                $this->context = new ClassLikeContext(
                    nameContext: $this->nameContext,
                    phpDocTags: $this->phpDocParser->parseNodePHPDoc($node),
                    name: $class,
                    parent: $parentClass,
                    interfaces: $this->namesToStrings($node->implements),
                );
            }

            if ($node instanceof Node\Stmt\Interface_) {
                $this->context = new ClassLikeContext(
                    nameContext: $this->nameContext,
                    phpDocTags: $this->phpDocParser->parseNodePHPDoc($node),
                    name: $class,
                    interfaces: $this->namesToStrings($node->extends),
                );

                return null;
            }

            if ($node instanceof Node\Stmt\Trait_) {
                $this->context = new ClassLikeContext(
                    nameContext: $this->nameContext,
                    phpDocTags: $this->phpDocParser->parseNodePHPDoc($node),
                    name: $class,
                );

                return null;
            }

            if ($node instanceof Node\Stmt\Enum_) {
                $this->context = new ClassLikeContext(
                    nameContext: $this->nameContext,
                    phpDocTags: $this->phpDocParser->parseNodePHPDoc($node),
                    name: $class,
                    interfaces: $this->namesToStrings($node->implements),
                );

                return null;
            }

            return null;
        }

        if ($node instanceof Node\Stmt\TraitUse) {
            \assert($this->context instanceof ClassLikeContext);

            /** @var non-empty-list<class-string> */
            $traits = $this->namesToStrings($node->traits);
            $this->context->addTraits($traits, $this->phpDocParser->parseNodePHPDoc($node));

            return null;
        }

        if ($node instanceof Node\Stmt\Property) {
            \assert($this->context instanceof ClassLikeContext);

            $this->context = new PropertyContext(
                classLikeContext: $this->context,
                phpDocTags: $this->phpDocParser->parseNodePHPDoc($node),
                static: $node->isStatic(),
                typeNode: $node->type,
            );

            return null;
        }

        if ($node instanceof Node\VarLikeIdentifier && $this->context instanceof PropertyContext) {
            /** @var non-empty-string $node->name */
            $this->context->setName($node->name);

            return null;
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            \assert($this->context instanceof ClassLikeContext);

            /** @var non-empty-string */
            $method = $node->name->toString();
            $this->context = new MethodContext(
                classLikeContext: $this->context,
                phpDocTags: $this->phpDocParser->parseNodePHPDoc($node),
                name: $method,
                static: $node->isStatic(),
                returnTypeNode: $node->returnType,
            );

            return null;
        }

        if ($node instanceof Node\Param) {
            \assert($this->context instanceof MethodContext);

            \assert($node->var instanceof Node\Expr\Variable && \is_string($node->var->name));
            /** @var non-empty-string $node->var->name */
            $this->context->addParameterType(
                name: $node->var->name,
                typeNode: $node->type,
                promoted: $node->flags & Class_::MODIFIER_PUBLIC || $node->flags & Class_::MODIFIER_PROTECTED || $node->flags & Class_::MODIFIER_PRIVATE,
            );

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            if ($this->context === null) {
                return null;
            }

            \assert($this->context instanceof ClassLikeContext);

            /** @var ClassLikeTypeReflection<T> */
            $this->reflection = $this->context->build($this->typeReflector, $this->typeResolver);
            $this->context = null;

            return NodeTraverser::STOP_TRAVERSAL;
        }

        if ($node instanceof Node\Stmt\Property) {
            \assert($this->context instanceof PropertyContext);

            $this->context = $this->context->finish($this->typeResolver);

            return null;
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            \assert($this->context instanceof MethodContext);

            $this->context = $this->context->finish($this->typeResolver);

            return null;
        }

        return null;
    }

    /**
     * @param array<Node\Name> $names
     * @return list<class-string>
     */
    private function namesToStrings(array $names): array
    {
        return array_map(
            static fn (Node\Name $name): string => /** @var class-string */ $name->toString(),
            array_values($names),
        );
    }
}
