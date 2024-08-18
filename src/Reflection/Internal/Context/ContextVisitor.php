<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Context;

use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use Typhoon\Reflection\Internal\Annotated\AnnotatedDeclarationsDiscoverer;
use Typhoon\Reflection\Internal\Annotated\NullAnnotatedDeclarationsDiscoverer;
use function Typhoon\Reflection\Internal\array_value_last;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ContextVisitor extends NodeVisitorAbstract implements ContextProvider
{
    private readonly Context $codeContext;

    /**
     * @var list<Context>
     */
    private array $contextStack = [];

    /**
     * @param ?non-empty-string $file
     */
    public function __construct(
        string $code,
        ?string $file,
        private readonly NameContext $nameContext,
        private readonly AnnotatedDeclarationsDiscoverer $annotatedDeclarationsDiscoverer = NullAnnotatedDeclarationsDiscoverer::Instance,
    ) {
        $this->codeContext = Context::start($code, $file);
    }

    public function get(): Context
    {
        return array_value_last($this->contextStack)
            ?? $this->codeContext->withNameContext($this->nameContext);
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->contextStack = [];

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            \assert($node->namespacedName !== null);

            $this->contextStack[] = $this->get()->enterFunction(
                name: $node->namespacedName->toString(),
                templateNames: $this->annotatedDeclarationsDiscoverer->discoverAnnotatedDeclarations($node)->templateNames,
            );

            return null;
        }

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            $line = $node->getStartLine();
            \assert($line > 0);
            $position = $node->getStartFilePos();
            \assert($position >= 0);

            $this->contextStack[] = $this->get()->enterAnonymousFunction(
                line: $line,
                column: $this->codeContext->column($position),
                templateNames: $this->annotatedDeclarationsDiscoverer->discoverAnnotatedDeclarations($node)->templateNames,
            );

            return null;
        }

        if ($node instanceof Class_) {
            $typeNames = $this->annotatedDeclarationsDiscoverer->discoverAnnotatedDeclarations($node);

            if ($node->name === null) {
                $line = $node->getStartLine();
                \assert($line > 0);
                $position = $node->getStartFilePos();
                \assert($position >= 0);

                $this->contextStack[] = $this->get()->enterAnonymousClass(
                    line: $line,
                    column: $this->codeContext->column($position),
                    parentName: $node->extends?->toString(),
                    aliasNames: $typeNames->aliasNames,
                    templateNames: $typeNames->templateNames,
                );

                return null;
            }

            \assert($node->namespacedName !== null);

            $this->contextStack[] = $this->get()->enterClass(
                name: $node->namespacedName->toString(),
                parentName: $node->extends?->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof Interface_) {
            \assert($node->namespacedName !== null);
            $typeNames = $this->annotatedDeclarationsDiscoverer->discoverAnnotatedDeclarations($node);

            $this->contextStack[] = $this->get()->enterInterface(
                name: $node->namespacedName->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof Trait_) {
            \assert($node->namespacedName !== null);
            $typeNames = $this->annotatedDeclarationsDiscoverer->discoverAnnotatedDeclarations($node);

            $this->contextStack[] = $this->get()->enterTrait(
                name: $node->namespacedName->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof Enum_) {
            \assert($node->namespacedName !== null);
            $typeNames = $this->annotatedDeclarationsDiscoverer->discoverAnnotatedDeclarations($node);

            $this->contextStack[] = $this->get()->enterEnum(
                name: $node->namespacedName->toString(),
                aliasNames: $typeNames->aliasNames,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        if ($node instanceof ClassMethod) {
            $typeNames = $this->annotatedDeclarationsDiscoverer->discoverAnnotatedDeclarations($node);

            $this->contextStack[] = $this->get()->enterMethod(
                name: $node->name->name,
                templateNames: $typeNames->templateNames,
            );

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): null|int|Node|array
    {
        if ($node instanceof FunctionLike || $node instanceof ClassLike) {
            array_pop($this->contextStack);

            return null;
        }

        return null;
    }
}
