<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Reflection\TypeContext\TypeContext;
use Typhoon\Type\Parameter;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ContextualPhpDocTypeReflector
{
    public function __construct(
        private TypeContext $typeContext = new TypeContext(),
    ) {}

    public function reflect(TypeNode $node): Type
    {
        if ($node instanceof NullableTypeNode) {
            return types::nullable($this->reflect($node->type));
        }

        if ($node instanceof UnionTypeNode) {
            return types::union(...array_map($this->reflect(...), $node->types));
        }

        if ($node instanceof IntersectionTypeNode) {
            return types::intersection(...array_map($this->reflect(...), $node->types));
        }

        if ($node instanceof ArrayTypeNode) {
            return types::array(value: $this->reflect($node->type));
        }

        if ($node instanceof ArrayShapeNode) {
            return $this->reflectArrayShape($node);
        }

        if ($node instanceof ObjectShapeNode) {
            return $this->reflectObjectShape($node);
        }

        if ($node instanceof ConstTypeNode) {
            return $this->reflectConstExpr($node);
        }

        if ($node instanceof CallableTypeNode) {
            return $this->reflectCallable($node);
        }

        if ($node instanceof IdentifierTypeNode) {
            return $this->reflectIdentifier($node->name);
        }

        if ($node instanceof GenericTypeNode) {
            return $this->reflectIdentifier($node->type->name, $node->genericTypes);
        }

        if ($node instanceof ConditionalTypeNode || $node instanceof ConditionalTypeForParameterNode) {
            return $this->reflectConditional($node);
        }

        throw new DefaultReflectionException(sprintf('Type node %s is not supported.', $node::class));
    }

    public function __clone()
    {
        $this->typeContext = clone $this->typeContext;
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function reflectIdentifier(string $name, array $genericTypes = []): Type
    {
        return match ($name) {
            'null' => types::null,
            'true' => types::true,
            'false' => types::false,
            'bool' => types::bool,
            'float' => types::float,
            'positive-int' => types::positiveInt,
            'negative-int' => types::negativeInt,
            'non-negative-int' => types::nonNegativeInt,
            'non-positive-int' => types::nonPositiveInt,
            'int' => match (\count($genericTypes)) {
                0 => types::int,
                2 => types::intRange(
                    min: $this->reflectIntLimit($genericTypes[0], 'min'),
                    max: $this->reflectIntLimit($genericTypes[1], 'max'),
                ),
                default => throw new DefaultReflectionException(sprintf('int range type should have 2 arguments, got %d.', \count($genericTypes)))
            },
            'int-mask', 'int-mask-of' => types::intMask(types::union(...array_map($this->reflect(...), $genericTypes))),
            'numeric' => types::numeric,
            'non-empty-string' => types::nonEmptyString,
            'string' => types::string,
            'non-falsy-string', 'truthy-string' => types::truthyString,
            'numeric-string' => types::numericString,
            'class-string' => match (\count($genericTypes)) {
                0 => types::classString,
                1 => types::classString($this->reflect($genericTypes[0])),
                default => throw new DefaultReflectionException(),
            },
            'array-key' => types::arrayKey,
            'key-of' => match ($number = \count($genericTypes)) {
                1 => types::key($this->reflect($genericTypes[0])),
                default => throw new DefaultReflectionException(sprintf('key-of type should have 1 argument, got %d.', $number)),
            },
            'value-of' => match ($number = \count($genericTypes)) {
                1 => types::value($this->reflect($genericTypes[0])),
                default => throw new DefaultReflectionException(sprintf('value-of type should have 1 argument, got %d.', $number)),
            },
            'literal-int' => types::literalInt,
            'literal-string' => types::literalString,
            'callable-string' => types::intersection(types::callable(), types::string),
            'interface-string', 'enum-string', 'trait-string' => types::classString,
            'callable-array' => types::intersection(types::callable(), types::array()),
            'resource', 'closed-resource', 'open-resource' => types::resource,
            'list' => match ($number = \count($genericTypes)) {
                0 => types::list(),
                1 => types::list($this->reflect($genericTypes[0])),
                default => throw new DefaultReflectionException(sprintf('list type should have at most 1 argument, got %d.', $number)),
            },
            'array' => match ($number = \count($genericTypes)) {
                0 => types::array(),
                1 => types::array(value: $this->reflect($genericTypes[0])),
                2 => types::array(...array_map($this->reflect(...), $genericTypes)),
                default => throw new DefaultReflectionException(sprintf('array type should have at most 2 arguments, got %d.', $number)),
            },
            'iterable' => match ($number = \count($genericTypes)) {
                0 => types::iterable(),
                1 => types::iterable(value: $this->reflect($genericTypes[0])),
                2 => types::iterable(...array_map($this->reflect(...), $genericTypes)),
                default => throw new DefaultReflectionException(sprintf('iterable type should have at most 2 arguments, got %d.', $number)),
            },
            'object' => types::object,
            'callable' => types::callable(),
            'mixed' => types::mixed,
            'void' => types::void,
            'scalar' => types::scalar,
            'never' => types::never,
            default => match (true) {
                str_starts_with($name, 'non-empty-') => types::nonEmpty($this->reflectIdentifier(substr($name, 10), $genericTypes)),
                default => $this->typeContext->resolveNameAsType($name, array_map($this->reflect(...), $genericTypes))
            },
        };
    }

    /**
     * @param 'min'|'max' $parameterName
     */
    private function reflectIntLimit(TypeNode $type, string $parameterName): ?int
    {
        if ($type instanceof IdentifierTypeNode && $type->name === $parameterName) {
            return null;
        }

        if (!$type instanceof ConstTypeNode) {
            throw new DefaultReflectionException(sprintf('Invalid int range %s argument: %s.', $parameterName, $type));
        }

        if (!$type->constExpr instanceof ConstExprIntegerNode) {
            throw new DefaultReflectionException(sprintf('Invalid int range %s argument: %s.', $parameterName, $type));
        }

        return (int) $type->constExpr->value;
    }

    private function reflectArrayShape(ArrayShapeNode $node): Type
    {
        $elements = [];

        foreach ($node->items as $item) {
            $type = types::arrayElement($this->reflect($item->valueType), $item->optional);

            if ($item->keyName === null) {
                $elements[] = $type;

                continue;
            }

            $keyName = $item->keyName;

            $key = match ($keyName::class) {
                ConstExprIntegerNode::class => $keyName->value,
                ConstExprStringNode::class => $keyName->value,
                IdentifierTypeNode::class => $keyName->name,
                default => throw new DefaultReflectionException(sprintf('%s is not supported.', $keyName::class)),
            };

            $elements[$key] = $type;
        }

        return types::arrayShape($elements, $node->sealed);
    }

    private function reflectObjectShape(ObjectShapeNode $node): Type
    {
        $properties = [];

        foreach ($node->items as $item) {
            $keyName = $item->keyName;

            $name = match ($keyName::class) {
                ConstExprStringNode::class => $keyName->value,
                IdentifierTypeNode::class => $keyName->name,
                default => throw new DefaultReflectionException(sprintf('%s is not supported.', $keyName::class)),
            };

            $properties[$name] = types::prop($this->reflect($item->valueType), $item->optional);
        }

        return types::objectShape($properties);
    }

    private function reflectConstExpr(ConstTypeNode $node): Type
    {
        $exprNode = $node->constExpr;

        if ($exprNode instanceof ConstExprIntegerNode) {
            return types::literalValue((int) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprFloatNode) {
            return types::literalValue((float) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprStringNode) {
            return types::literalValue($exprNode->value);
        }

        if ($exprNode instanceof ConstExprTrueNode) {
            return types::true;
        }

        if ($exprNode instanceof ConstExprFalseNode) {
            return types::false;
        }

        if ($exprNode instanceof ConstExprNullNode) {
            return types::null;
        }

        if ($exprNode instanceof ConstFetchNode) {
            if ($exprNode->className === '') {
                return types::constant($exprNode->name);
            }

            $class = $this->typeContext->resolveNameAsType($exprNode->className);

            if ($exprNode->name === 'class') {
                return types::classString($class);
            }

            return types::classConstant($class, $exprNode->name);
        }

        throw new DefaultReflectionException(sprintf('PhpDoc node %s is not supported.', $exprNode::class));
    }

    private function reflectCallable(CallableTypeNode $node): Type
    {
        if ($node->identifier->name === 'callable') {
            return types::callable(
                parameters: $this->reflectCallableParameters($node->parameters),
                return: $this->reflect($node->returnType),
            );
        }

        if ($this->typeContext->resolveNameAsClass($node->identifier->name) === \Closure::class) {
            return types::closure(
                parameters: $this->reflectCallableParameters($node->parameters),
                return: $this->reflect($node->returnType),
            );
        }

        throw new DefaultReflectionException(sprintf('PhpDoc type "%s" is not supported.', $node));
    }

    /**
     * @param list<CallableTypeParameterNode> $nodes
     * @return list<Parameter>
     */
    private function reflectCallableParameters(array $nodes): array
    {
        return array_map(
            fn(CallableTypeParameterNode $parameter): Parameter => types::param(
                type: $this->reflect($parameter->type),
                hasDefault: $parameter->isOptional,
                variadic: $parameter->isVariadic,
                byReference: $parameter->isReference,
                name: $parameter->parameterName ?: null,
            ),
            $nodes,
        );
    }

    private function reflectConditional(ConditionalTypeNode|ConditionalTypeForParameterNode $node): Type
    {
        if ($node instanceof ConditionalTypeNode) {
            $subject = $this->reflect($node->subjectType);
        } else {
            /** @var non-empty-string */
            $name = substr($node->parameterName, 1);
            $subject = types::arg($name);
        }

        if ($node->negated) {
            return types::conditional(
                subject: $subject,
                if: $this->reflect($node->targetType),
                then: $this->reflect($node->else),
                else: $this->reflect($node->if),
            );
        }

        return types::conditional(
            subject: $subject,
            if: $this->reflect($node->targetType),
            then: $this->reflect($node->if),
            else: $this->reflect($node->else),
        );
    }
}
