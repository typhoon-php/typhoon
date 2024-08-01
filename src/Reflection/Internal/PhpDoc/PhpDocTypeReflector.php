<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

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
use PHPStan\PhpDocParser\Ast\Type\OffsetAccessTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Annotated\CustomTypeResolver;
use Typhoon\Reflection\Annotated\NullCustomTypeResolver;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Type\Parameter;
use Typhoon\Type\ShapeElement;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\PhpDoc
 */
final class PhpDocTypeReflector
{
    public function __construct(
        private readonly Context $context,
        private readonly CustomTypeResolver $customTypeResolver = new NullCustomTypeResolver(),
    ) {}

    /**
     * @return non-empty-string
     */
    public function resolveClass(IdentifierTypeNode $node): string
    {
        return $this->context->resolveClassName($node->name);
    }

    /**
     * @return ($node is null ? null : Type)
     * @throws InvalidPhpDocType
     */
    public function reflectType(?TypeNode $node): ?Type
    {
        if ($node === null) {
            return null;
        }

        if ($node instanceof NullableTypeNode) {
            return types::nullable($this->reflectType($node->type));
        }

        if ($node instanceof UnionTypeNode) {
            return types::union(...array_map($this->reflectType(...), $node->types));
        }

        if ($node instanceof IntersectionTypeNode) {
            return types::intersection(...array_map($this->reflectType(...), $node->types));
        }

        if ($node instanceof ArrayTypeNode) {
            return types::array(value: $this->reflectType($node->type));
        }

        if ($node instanceof ArrayShapeNode) {
            if ($node->kind === ArrayShapeNode::KIND_LIST) {
                return $this->reflectListShape($node);
            }

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

        if ($node instanceof OffsetAccessTypeNode) {
            return types::offset($this->reflectType($node->type), $this->reflectType($node->offset));
        }

        throw new InvalidPhpDocType(\sprintf('Type node %s is not supported', $node::class));
    }

    /**
     * @param non-empty-string $name
     * @param list<TypeNode> $genericNodes
     */
    private function reflectIdentifier(string $name, array $genericNodes = []): Type
    {
        $typeArguments = array_map($this->reflectType(...), $genericNodes);

        $customType = $this->customTypeResolver->resolveCustomType($name, $typeArguments, $this->context);

        if ($customType !== null) {
            return $customType;
        }

        return match ($name) {
            'null' => types::null,
            'true' => types::true,
            'false' => types::false,
            'bool', 'boolean' => types::bool,
            'float', 'double' => match (\count($genericNodes)) {
                0 => types::float,
                2 => types::floatRange(
                    min: $this->reflectFloatLimit($genericNodes[0], 'min'),
                    max: $this->reflectFloatLimit($genericNodes[1], 'max'),
                ),
                default => throw new InvalidPhpDocType(\sprintf('float range type should have 2 type arguments, got %d', \count($genericNodes)))
            },
            'positive-int' => types::positiveInt,
            'negative-int' => types::negativeInt,
            'non-negative-int' => types::nonNegativeInt,
            'non-positive-int' => types::nonPositiveInt,
            'non-zero-int' => types::nonZeroInt,
            'int', 'integer' => match (\count($genericNodes)) {
                0 => types::int,
                2 => types::intRange(
                    min: $this->reflectIntLimit($genericNodes[0], 'min'),
                    max: $this->reflectIntLimit($genericNodes[1], 'max'),
                ),
                default => throw new InvalidPhpDocType(\sprintf('int range type should have 2 type arguments, got %d', \count($genericNodes)))
            },
            'int-mask', 'int-mask-of' => types::intMaskOf(types::union(...$typeArguments)),
            'numeric' => types::numeric,
            'non-empty-string' => types::nonEmptyString,
            'string' => types::string,
            'non-falsy-string', 'truthy-string' => types::truthyString,
            'numeric-string' => types::numericString,
            'class-string' => match (\count($typeArguments)) {
                0 => types::classString,
                1 => types::classString($typeArguments[0]),
                default => throw new InvalidPhpDocType(),
            },
            'array-key' => types::arrayKey,
            'key-of' => match ($number = \count($typeArguments)) {
                1 => types::keyOf($typeArguments[0]),
                default => throw new InvalidPhpDocType(\sprintf('key-of type should have 1 type argument, got %d', $number)),
            },
            'value-of' => match ($number = \count($typeArguments)) {
                1 => types::valueOf($typeArguments[0]),
                default => throw new InvalidPhpDocType(\sprintf('value-of type should have 1 type argument, got %d', $number)),
            },
            'literal-int' => types::literalInt,
            'literal-string' => types::literalString,
            'literal-float' => types::literalFloat,
            'callable-string' => types::callableString(),
            'interface-string', 'enum-string', 'trait-string' => types::classString,
            'callable-array' => types::callableArray(),
            'resource', 'closed-resource', 'open-resource' => types::resource,
            'list' => match ($number = \count($typeArguments)) {
                0 => types::list(),
                1 => types::list($typeArguments[0]),
                default => throw new InvalidPhpDocType(\sprintf('list type should have at most 1 type argument, got %d', $number)),
            },
            'non-empty-list' => match ($number = \count($typeArguments)) {
                0 => types::nonEmptyList(),
                1 => types::nonEmptyList($typeArguments[0]),
                default => throw new InvalidPhpDocType(\sprintf('list type should have at most 1 type argument, got %d', $number)),
            },
            'array' => match ($number = \count($typeArguments)) {
                0 => types::array,
                1 => types::array(value: $typeArguments[0]),
                2 => types::array(...$typeArguments),
                default => throw new InvalidPhpDocType(\sprintf('array type should have at most 2 type arguments, got %d', $number)),
            },
            'non-empty-array' => match ($number = \count($typeArguments)) {
                0 => types::nonEmptyArray(),
                1 => types::nonEmptyArray(value: $typeArguments[0]),
                2 => types::nonEmptyArray(...$typeArguments),
                default => throw new InvalidPhpDocType(\sprintf('array type should have at most 2 type arguments, got %d', $number)),
            },
            'iterable' => match ($number = \count($typeArguments)) {
                0 => types::iterable,
                1 => types::iterable(value: $typeArguments[0]),
                2 => types::iterable(...$typeArguments),
                default => throw new InvalidPhpDocType(\sprintf('iterable type should have at most 2 type arguments, got %d', $number)),
            },
            'object' => types::object,
            'callable' => types::callable,
            'mixed' => types::mixed,
            'void' => types::void,
            'scalar' => types::scalar,
            'never' => types::never,
            default => match ($class = $this->context->resolveClassName($name)) {
                \Traversable::class, \Iterator::class, \IteratorAggregate::class => match ($number = \count($typeArguments)) {
                    1 => types::object($class, [types::mixed, $typeArguments[0]]),
                    0, 2 => types::object($class, $typeArguments),
                    default => throw new InvalidPhpDocType(\sprintf('%s type should have at most 2 type arguments, got %d', $class, $number)),
                },
                \Generator::class => match ($number = \count($typeArguments)) {
                    1 => types::generator(value: $typeArguments[0]),
                    0, 2, 3, 4 => types::generator(...$typeArguments),
                    default => throw new InvalidPhpDocType(\sprintf('Generator type should have at most 4 type arguments, got %d', $number)),
                },
                default => $this->context->resolveNameAsType($name, $typeArguments)
            },
        };
    }

    /**
     * @param 'min'|'max' $name
     */
    private function reflectIntLimit(TypeNode $type, string $name): Type
    {
        if ($type instanceof IdentifierTypeNode && $type->name === $name) {
            return $name === 'min' ? types::PHP_INT_MIN : types::PHP_INT_MAX;
        }

        return $this->reflectType($type);
    }

    /**
     * @param 'min'|'max' $name
     */
    private function reflectFloatLimit(TypeNode $type, string $name): Type
    {
        if ($type instanceof IdentifierTypeNode && $type->name === $name) {
            return $name === 'min' ? types::PHP_FLOAT_MIN : types::PHP_FLOAT_MAX;
        }

        return $this->reflectType($type);
    }

    private function reflectListShape(ArrayShapeNode $node): Type
    {
        $elements = [];

        foreach ($node->items as $item) {
            $keyName = $item->keyName;
            $type = new ShapeElement($this->reflectType($item->valueType), $item->optional);

            if ($keyName === null) {
                $elements[] = $type;

                continue;
            }

            if ($keyName instanceof ConstExprIntegerNode) {
                $key = (int) $keyName->value;

                if ($key < 0) {
                    throw new InvalidPhpDocType(\sprintf('Unexpected negative key %d in a list shape', $key));
                }

                $elements[$key] = $type;

                continue;
            }

            throw new InvalidPhpDocType(\sprintf('Unexpected key "%s" in a list shape', $keyName::class));
        }

        if ($node->sealed) {
            if (!array_is_list($elements)) {
                throw new InvalidPhpDocType(\sprintf(
                    'Keys in a sealed shape must be a list, got %s',
                    implode(', ', array_keys($elements)),
                ));
            }

            return types::listShape($elements);
        }

        return types::unsealedListShape($elements);
    }

    private function reflectArrayShape(ArrayShapeNode $node): Type
    {
        $elements = [];

        foreach ($node->items as $item) {
            $keyName = $item->keyName;
            $type = new ShapeElement($this->reflectType($item->valueType), $item->optional);

            if ($keyName === null) {
                $elements[] = $type;

                continue;
            }

            $key = match (true) {
                $keyName instanceof ConstExprIntegerNode => (int) $keyName->value,
                $keyName instanceof ConstExprStringNode => $keyName->value,
                $keyName instanceof IdentifierTypeNode => $keyName->name,
            };
            $elements[$key] = $type;
        }

        if ($node->sealed) {
            return types::arrayShape($elements);
        }

        return types::unsealedArrayShape($elements);
    }

    private function reflectObjectShape(ObjectShapeNode $node): Type
    {
        $properties = [];

        foreach ($node->items as $item) {
            $keyName = $item->keyName;

            $name = match ($keyName::class) {
                ConstExprStringNode::class => $keyName->value,
                IdentifierTypeNode::class => $keyName->name,
                default => throw new InvalidPhpDocType(\sprintf('%s is not supported', $keyName::class)),
            };

            $properties[$name] = new ShapeElement($this->reflectType($item->valueType), $item->optional);
        }

        return types::objectShape($properties);
    }

    private function reflectConstExpr(ConstTypeNode $node): Type
    {
        $exprNode = $node->constExpr;

        if ($exprNode instanceof ConstExprNullNode) {
            return types::null;
        }

        if ($exprNode instanceof ConstExprTrueNode) {
            return types::true;
        }

        if ($exprNode instanceof ConstExprFalseNode) {
            return types::false;
        }

        if ($exprNode instanceof ConstExprIntegerNode) {
            return types::int((int) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprFloatNode) {
            return types::float((float) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprStringNode) {
            return types::string($exprNode->value);
        }

        if ($exprNode instanceof ConstFetchNode) {
            if ($exprNode->className === '') {
                // TODO
                throw new InvalidPhpDocType(\sprintf('PhpDoc node %s with empty class is not supported', $exprNode::class));
            }

            $class = $this->context->resolveNameAsType($exprNode->className);

            if ($exprNode->name === 'class') {
                return types::class($class);
            }

            return types::classConstant($class, $exprNode->name);
        }

        throw new InvalidPhpDocType(\sprintf('PhpDoc node %s is not supported', $exprNode::class));
    }

    private function reflectCallable(CallableTypeNode $node): Type
    {
        if ($node->identifier->name === 'callable') {
            return types::callable(
                parameters: $this->reflectCallableParameters($node->parameters),
                return: $this->reflectType($node->returnType),
            );
        }

        $class = $this->resolveClass($node->identifier);

        if ($class === \Closure::class) {
            return types::closure(
                parameters: $this->reflectCallableParameters($node->parameters),
                return: $this->reflectType($node->returnType),
            );
        }

        throw new InvalidPhpDocType(\sprintf('PhpDoc type "%s" is not supported', $node));
    }

    /**
     * @param list<CallableTypeParameterNode> $nodes
     * @return list<Parameter>
     */
    private function reflectCallableParameters(array $nodes): array
    {
        return array_map(
            fn(CallableTypeParameterNode $parameter): Parameter => new Parameter(
                type: $this->reflectType($parameter->type),
                hasDefault: $parameter->isOptional,
                variadic: $parameter->isVariadic,
                byReference: $parameter->isReference,
            ),
            $nodes,
        );
    }

    private function reflectConditional(ConditionalTypeNode|ConditionalTypeForParameterNode $node): Type
    {
        if ($node->negated) {
            return types::conditional(
                subject: $this->reflectConditionalSubject($node),
                if: $this->reflectType($node->targetType),
                then: $this->reflectType($node->else),
                else: $this->reflectType($node->if),
            );
        }

        return types::conditional(
            subject: $this->reflectConditionalSubject($node),
            if: $this->reflectType($node->targetType),
            then: $this->reflectType($node->if),
            else: $this->reflectType($node->else),
        );
    }

    public function reflectConditionalSubject(ConditionalTypeNode|ConditionalTypeForParameterNode $node): Type
    {
        if ($node instanceof ConditionalTypeNode) {
            return $this->reflectType($node->subjectType);
        }

        $name = ltrim($node->parameterName, '$');
        \assert($name !== '', 'Parameter name must not be empty');

        $id = $this->context->currentId;

        if ($id instanceof NamedFunctionId
         || $id instanceof AnonymousFunctionId
         || $id instanceof MethodId
        ) {
            return types::arg(Id::parameter($id, $name));
        }

        if ($id === null) {
            throw new InvalidPhpDocType('Conditional type in global scope is not supported');
        }

        throw new InvalidPhpDocType(\sprintf('Conditional type on %s is not supported', $id->describe()));
    }
}
