<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt\Class_;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ConstantExpressionTypeReflector
{
    private readonly ConstExprEvaluator $evaluator;

    public function __construct(
        private readonly Context $context,
    ) {
        $this->evaluator = new ConstExprEvaluator();
    }

    public function reflect(?Expr $expr): ?Type
    {
        if ($expr === null) {
            return null;
        }

        try {
            return $this->doReflect($expr);
        } catch (ConstExprEvaluationException) {
            return null;
        }
    }

    /**
     * @throws ConstExprEvaluationException
     */
    private function doReflect(Expr $expr): Type
    {
        return match (true) {
            $expr instanceof Scalar\String_ => types::string($expr->value),
            $expr instanceof Scalar\LNumber => types::int($expr->value),
            $expr instanceof Scalar\DNumber => types::float($expr->value),
            $expr instanceof Expr\Array_ => $this->reflectArray($expr),
            $expr instanceof Scalar\MagicConst\Line => types::int($expr->getStartLine()),
            $expr instanceof Scalar\MagicConst\File => types::string($this->context->file ?? ''),
            $expr instanceof Scalar\MagicConst\Dir => types::string($this->context->directory() ?? ''),
            $expr instanceof Scalar\MagicConst\Namespace_ => types::string($this->context->namespace()),
            // $expr instanceof Scalar\MagicConst\Function_ => $this->context->magicFunction(),
            $expr instanceof Scalar\MagicConst\Class_ => types::self(resolvedClass: $this->context->self),
            $expr instanceof Scalar\MagicConst\Trait_ => types::string($this->context->trait?->name ?? ''),
            // $expr instanceof Scalar\MagicConst\Method => $this->context->magicMethod(),
            $expr instanceof Expr\ConstFetch => $this->reflectConstant($expr->name),
            $expr instanceof Expr\ClassConstFetch => types::classConstant(
                class: $this->reflectClassName($expr->class),
                name: $this->reflectClassConstantName($expr->name),
            ),
            $expr instanceof Expr\New_ => $this->reflectClassName($expr->class),
            // $expr instanceof Expr\Ternary => ,
            default => types::value($this->evaluator->evaluateSilently($expr)),
        };
    }

    /**
     * @throws ConstExprEvaluationException
     */
    private function reflectArray(Expr\Array_ $expr): Type
    {
        /** @var list<Expr\ArrayItem> */
        $items = $expr->items;
        $elements = [];

        foreach ($items as $item) {
            if ($item->unpack) {
                throw new ConstExprEvaluationException();
            }

            if ($item->key === null) {
                $elements[] = $this->doReflect($item->value);

                continue;
            }

            /** @psalm-suppress MixedArrayOffset */
            $elements[$this->evaluator->evaluateSilently($item->key)] = $this->doReflect($item->value);
        }

        return types::arrayShape($elements);
    }

    /**
     * @throws ConstExprEvaluationException
     */
    private function reflectConstant(Name $name): Type
    {
        $lowerStringName = $name->toLowerString();

        if ($lowerStringName === 'null') {
            return types::null;
        }

        if ($lowerStringName === 'true') {
            return types::true;
        }

        if ($lowerStringName === 'false') {
            return types::false;
        }

        $namespacedName = $name->getAttribute('namespacedName');

        if ($namespacedName instanceof FullyQualified) {
            return new UnresolvedConstantType($namespacedName->toString(), $name->toString());
        }

        return types::constant($name->toString());
    }

    /**
     * @throws ConstExprEvaluationException
     */
    private function reflectClassName(Name|Expr|Class_ $name): Type
    {
        if ($name instanceof Expr) {
            $name = $this->evaluator->evaluateSilently($name);

            if (\is_string($name) && $name !== '') {
                return types::object($name);
            }

            throw new ConstExprEvaluationException();
        }

        if ($name instanceof Name) {
            return $this->context->resolveNameAsType($name->toCodeString());
        }

        throw new \LogicException('Unexpected anonymous class in a constant expression');
    }

    /**
     * @return non-empty-string
     * @throws ConstExprEvaluationException
     */
    private function reflectClassConstantName(Identifier|Expr $name): string
    {
        if ($name instanceof Identifier) {
            return $name->name;
        }

        $name = $this->evaluator->evaluateSilently($name);

        if (\is_string($name) && $name !== '') {
            return $name;
        }

        throw new ConstExprEvaluationException();
    }
}
