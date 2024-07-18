<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\TypeContext;

use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\TemplateId;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TypeContextVisitor extends NodeVisitorAbstract implements TypeContextProvider
{
    /**
     * @var list<TypeContext>
     */
    private array $contextStack = [];

    /**
     * @var ?non-negative-int
     */
    private ?int $codeLength = null;

    /**
     * @param ?non-empty-string $file
     */
    public function __construct(
        private readonly NameContext $nameContext,
        private readonly AnnotatedTypesDriver $annotatedTypesDriver,
        private readonly string $code,
        private readonly ?string $file = null,
    ) {}

    public function get(): TypeContext
    {
        return end($this->contextStack) ?: new TypeContext($this->nameContext);
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->contextStack = [];

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            $this->contextStack[] = $this->buildFunctionContext($node);

            return null;
        }

        if ($node instanceof ClassLike) {
            $this->contextStack[] = $this->buildClassContext($node);

            return null;
        }

        if ($node instanceof Const_) {
            $typeContext = $this->get();

            if ($typeContext->id instanceof NamedClassId || $typeContext->id instanceof AnonymousClassId) {
                $this->contextStack[] = new TypeContext(
                    nameContext: $this->nameContext,
                    id: Id::classConst($typeContext->id, $node->name->name),
                    self: $typeContext->self,
                    parent: $typeContext->parent,
                    aliases: $typeContext->aliases,
                    templates: $typeContext->templates,
                );

                return null;
            }

            return null;
        }

        if ($node instanceof PropertyProperty) {
            $typeContext = $this->get();
            \assert($typeContext->id instanceof NamedClassId || $typeContext->id instanceof AnonymousClassId);

            $this->contextStack[] = new TypeContext(
                nameContext: $this->nameContext,
                id: Id::property($typeContext->id, $node->name->name),
                self: $typeContext->self,
                parent: $typeContext->parent,
                aliases: $typeContext->aliases,
                templates: $typeContext->templates,
            );

            return null;
        }

        if ($node instanceof ClassMethod) {
            $typeContext = $this->get();
            \assert($typeContext->id instanceof NamedClassId || $typeContext->id instanceof AnonymousClassId);
            $typeDeclarations = $this->annotatedTypesDriver->reflectTypeDeclarations($node);
            $methodId = Id::method($typeContext->id, $node->name->name);

            $this->contextStack[] = new TypeContext(
                nameContext: $this->nameContext,
                id: $methodId,
                self: $typeContext->self,
                parent: $typeContext->parent,
                aliases: $typeContext->aliases,
                templates: [
                    ...$typeContext->templates,
                    ...array_map(
                        static fn(string $name): TemplateId => Id::template($methodId, $name),
                        $typeDeclarations->templateNames,
                    ),
                ],
            );

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Function_ || $node instanceof ClassLike || $node instanceof PropertyProperty || $node instanceof ClassMethod) {
            array_pop($this->contextStack);

            return null;
        }

        if ($node instanceof Const_) {
            $typeContext = $this->get();

            if ($typeContext->id instanceof ClassConstId
                || $typeContext->id instanceof NamedClassId
                || $typeContext->id instanceof AnonymousClassId
            ) {
                array_pop($this->contextStack);

                return null;
            }

            return null;
        }

        return null;
    }

    private function buildFunctionContext(Function_ $node): TypeContext
    {
        $typeContext = $this->get();
        $typeDeclarations = $this->annotatedTypesDriver->reflectTypeDeclarations($node);

        \assert($node->namespacedName !== null);
        $functionId = Id::namedFunction($node->namespacedName->toString());

        return new TypeContext(
            nameContext: $this->nameContext,
            id: $functionId,
            self: $typeContext->self,
            parent: $typeContext->parent,
            aliases: $typeContext->aliases,
            templates: array_map(
                static fn(string $name): TemplateId => Id::template($functionId, $name),
                $typeDeclarations->templateNames,
            ),
        );
    }

    private function buildClassContext(ClassLike $node): TypeContext
    {
        $typeDeclarations = $this->annotatedTypesDriver->reflectTypeDeclarations($node);

        if ($node->name === null) {
            $startLine = $node->getStartLine();
            \assert($startLine > 0);

            $classId = Id::anonymousClass(
                file: $this->file ?? throw new \LogicException('No file for anonymous class'),
                line: $startLine,
                column: $this->column($node),
            );
        } else {
            \assert($node->namespacedName !== null);
            $classId = Id::namedClass($node->namespacedName->toString());
        }

        return new TypeContext(
            nameContext: $this->nameContext,
            id: $classId,
            self: $node instanceof Trait_ ? null : $classId,
            parent: $node instanceof Class_ && $node->extends !== null ? Id::namedClass($node->extends->toString()) : null,
            aliases: array_map(
                static fn(string $name): AliasId => Id::alias($classId, $name),
                $typeDeclarations->aliasNames,
            ),
            templates: array_map(
                static fn(string $name): TemplateId => Id::template($classId, $name),
                $typeDeclarations->templateNames,
            ),
        );
    }

    /**
     * @return positive-int
     */
    private function column(Node $node): int
    {
        $this->codeLength ??= \strlen($this->code);
        $startFilePosition = $node->getStartFilePos();
        \assert($startFilePosition >= 0);

        $lineStartPosition = strrpos($this->code, "\n", $startFilePosition - $this->codeLength);

        if ($lineStartPosition === false) {
            $lineStartPosition = -1;
        }

        $column = $startFilePosition - $lineStartPosition;
        \assert($column > 0);

        return $column;
    }
}
