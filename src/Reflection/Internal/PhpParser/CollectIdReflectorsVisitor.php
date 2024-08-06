<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Context\ContextProvider;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class CollectIdReflectorsVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var IdMap<ConstantId|NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(): TypedMap>
     */
    public IdMap $idReflectors;

    public function __construct(
        private readonly NodeReflector $nodeReflector,
        private readonly ContextProvider $contextProvider,
        private readonly ConstExprEvaluator $evaluator = new ConstExprEvaluator(),
    ) {
        /** @var IdMap<ConstantId|NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(): TypedMap> */
        $this->idReflectors = new IdMap();
    }

    /**
     * @throws ConstExprEvaluationException
     */
    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Function_) {
            $context = $this->contextProvider->get();
            \assert($context->currentId instanceof NamedFunctionId);

            $nodeReflector = $this->nodeReflector;
            $this->idReflectors = $this->idReflectors->with(
                $context->currentId,
                static fn(): TypedMap => $nodeReflector->reflectFunction($node, $context),
            );

            return null;
        }

        if ($node instanceof ClassLike) {
            $context = $this->contextProvider->get();
            \assert($context->currentId instanceof NamedClassId || $context->currentId instanceof AnonymousClassId);

            $nodeReflector = $this->nodeReflector;
            $this->idReflectors = $this->idReflectors->with(
                $context->currentId,
                static fn(): TypedMap => $nodeReflector->reflectClassLike($node, $context),
            );

            return null;
        }

        if ($node instanceof ClassMethod) {
            NodeContextAttribute::set($node, $this->contextProvider->get());

            return null;
        }

        if ($node instanceof Const_) {
            $context = $this->contextProvider->get();
            $nodeReflector = $this->nodeReflector;

            foreach ($node->consts as $key => $const) {
                \assert($const->namespacedName !== null);

                $this->idReflectors = $this->idReflectors->with(
                    Id::constant($const->namespacedName->toString()),
                    static fn(): TypedMap => $nodeReflector->reflectConstant($node, $key, $context),
                );
            }

            return null;
        }

        if ($node instanceof FuncCall
            && $node->name instanceof Name
            && strtolower($node->name->toString()) === 'define'
        ) {
            $nameArg = $node->args[0] ?? $node->args['constant_name'] ?? null;
            $valueArg = $node->args[1] ?? $node->args['value'] ?? null;

            if (!($nameArg instanceof Arg && $valueArg instanceof Arg)) {
                return null;
            }

            $name = $this->evaluator->evaluateSilently($nameArg->value);
            \assert(\is_string($name) && $name !== '');

            $context = $this->contextProvider->get();
            $nodeReflector = $this->nodeReflector;
            $this->idReflectors = $this->idReflectors->with(
                Id::constant($name),
                static fn(): TypedMap => $nodeReflector->reflectDefine($node, $context),
            );

            return null;
        }

        return null;
    }
}
