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
use Typhoon\Type;
use Typhoon\Type\types;
use Typhoon\TypeStringifier\TypeStringifier;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ContextualPhpDocTypeReflector
{
    public function __construct(
        private TypeContext $typeContext = new TypeContext(),
    ) {}

    public function reflect(TypeNode $node): Type\Type
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
            return types::array(valueType: $this->reflect($node->type));
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
    private function reflectIdentifier(string $name, array $genericTypes = []): Type\Type
    {
        if ($name === 'int') {
            return $this->reflectInt($genericTypes);
        }

        if ($name === 'int-mask') {
            return $this->reflectIntMask($genericTypes);
        }

        if ($name === 'int-mask-of') {
            return $this->reflectIntMaskOf($genericTypes);
        }

        if ($name === 'list') {
            return $this->reflectList($genericTypes);
        }

        if ($name === 'non-empty-list') {
            return $this->reflectNonEmptyList($genericTypes);
        }

        if ($name === 'array') {
            return $this->reflectArray($genericTypes);
        }

        if ($name === 'non-empty-array') {
            return $this->reflectNonEmptyArray($genericTypes);
        }

        if ($name === 'iterable') {
            return $this->reflectIterable($genericTypes);
        }

        if ($name === 'class-string') {
            return $this->reflectClassString($genericTypes);
        }

        if ($name === 'key-of') {
            return $this->reflectKeyOf($genericTypes);
        }

        if ($name === 'value-of') {
            return $this->reflectValueOf($genericTypes);
        }

        // todo warning

        /** @var Type\Type */
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
            'numeric' => types::numeric,
            'string' => types::string,
            'non-empty-string' => types::nonEmptyString,
            'non-falsy-string', 'truthy-string' => types::truthyString,
            'numeric-string' => types::numericString,
            'array-key' => types::arrayKey,
            'literal-int' => types::literalInt,
            'literal-string' => types::literalString,
            'callable-string' => types::callableString,
            'interface-string' => types::interfaceString,
            'enum-string' => types::enumString,
            'trait-string' => types::traitString,
            'callable-array' => types::callableArray,
            'resource' => types::resource,
            'closed-resource' => types::closedResource,
            'object' => types::object,
            'callable' => types::callable(),
            'mixed' => types::mixed,
            'void' => types::void,
            'scalar' => types::scalar,
            'never' => types::never,
            default => $this->reflectName($name, $genericTypes),
        };
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function reflectName(string $name, array $genericTypes): Type\Type
    {
        $type = $this->typeContext->resolveNameAsType($name);

        if ($genericTypes === []) {
            if ($type instanceof Type\NamedObjectType && $type->class === \Closure::class) {
                return types::closure();
            }

            return $type;
        }

        if ($type instanceof Type\NamedObjectType) {
            return types::object($type->class, ...array_map($this->reflect(...), $genericTypes));
        }

        if ($type instanceof Type\StaticType) {
            return types::static($type->declaredAtClass, ...array_map($this->reflect(...), $genericTypes));
        }

        throw new DefaultReflectionException(sprintf('Types %s does not support template arguments.', TypeStringifier::stringify($type)));
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectInt(array $templateArguments): Type\Type
    {
        return match (\count($templateArguments)) {
            0 => types::int,
            2 => types::intRange(
                min: $this->reflectIntLimit($templateArguments[0], 'min'),
                max: $this->reflectIntLimit($templateArguments[1], 'max'),
            ),
            default => throw new DefaultReflectionException(sprintf('int range type should have 2 arguments, got %d.', \count($templateArguments)))
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

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectIntMask(array $templateArguments): Type\IntMaskType
    {
        if ($templateArguments === []) {
            throw new DefaultReflectionException('int-mask type should have at least 1 argument.');
        }

        return types::intMask(...array_map($this->reflectIntMaskInt(...), $templateArguments));
    }

    /**
     * @return int<0, max>
     */
    private function reflectIntMaskInt(TypeNode $node): int
    {
        if (!$node instanceof ConstTypeNode) {
            throw new DefaultReflectionException(sprintf('Invalid int-mask argument: %s.', $node));
        }

        if (!$node->constExpr instanceof ConstExprIntegerNode) {
            throw new DefaultReflectionException(sprintf('Invalid int-mask argument: %s.', $node));
        }

        $value = (int) $node->constExpr->value;

        if ($value < 0) {
            throw new DefaultReflectionException(sprintf('Invalid int-mask argument: %d.', $value));
        }

        return $value;
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectIntMaskOf(array $templateArguments): Type\IntMaskOfType
    {
        if (\count($templateArguments) !== 1) {
            throw new DefaultReflectionException(sprintf('int-mask-of type should have 1 argument, got %d.', \count($templateArguments)));
        }

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return types::intMaskOf($this->reflect($templateArguments[0]));
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectList(array $templateArguments): Type\Type
    {
        return match ($number = \count($templateArguments)) {
            0 => types::list(),
            1 => types::list($this->reflect($templateArguments[0])),
            default => throw new DefaultReflectionException(sprintf('list type should have at most 1 argument, got %d.', $number)),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectNonEmptyList(array $templateArguments): Type\Type
    {
        return match ($number = \count($templateArguments)) {
            0 => types::nonEmptyList(),
            1 => types::nonEmptyList($this->reflect($templateArguments[0])),
            default => throw new DefaultReflectionException(sprintf('non-empty-list type should have at most 1 argument, got %d.', $number)),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectArray(array $templateArguments): Type\Type
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @todo check array-key
         */
        return match ($number = \count($templateArguments)) {
            0 => types::array(),
            1 => types::array(valueType: $this->reflect($templateArguments[0])),
            2 => types::array(...array_map($this->reflect(...), $templateArguments)),
            default => throw new DefaultReflectionException(sprintf('array type should have at most 2 arguments, got %d.', $number)),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectNonEmptyArray(array $templateArguments): Type\Type
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @todo check array-key
         */
        return match ($number = \count($templateArguments)) {
            0 => types::nonEmptyArray(),
            1 => types::nonEmptyArray(valueType: $this->reflect($templateArguments[0])),
            2 => types::nonEmptyArray(...array_map($this->reflect(...), $templateArguments)),
            default => throw new DefaultReflectionException(sprintf('non-empty-array type should have at most 2 arguments, got %d.', $number)),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectIterable(array $templateArguments): Type\Type
    {
        return match ($number = \count($templateArguments)) {
            0 => types::iterable(),
            1 => types::iterable(valueType: $this->reflect($templateArguments[0])),
            2 => types::iterable(...array_map($this->reflect(...), $templateArguments)),
            default => throw new DefaultReflectionException(sprintf('iterable type should have at most 2 arguments, got %d.', $number)),
        };
    }

    private function reflectArrayShape(ArrayShapeNode $node): Type\ArrayShapeType
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

    private function reflectObjectShape(ObjectShapeNode $node): Type\ObjectShapeType
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

    private function reflectConstExpr(ConstTypeNode $node): Type\Type
    {
        $exprNode = $node->constExpr;

        if ($exprNode instanceof ConstExprIntegerNode) {
            return types::int((int) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprFloatNode) {
            return types::float((float) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprStringNode) {
            return types::string($exprNode->value);
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

            $class = $this->typeContext->resolveNameAsClass($exprNode->className);

            if ($exprNode->name === 'class') {
                return types::classString($class);
            }

            return types::classConstant($class, $exprNode->name);
        }

        throw new DefaultReflectionException(sprintf('PhpDoc node %s is not supported.', $exprNode::class));
    }

    private function reflectCallable(CallableTypeNode $node): Type\Type
    {
        if ($node->identifier->name === 'callable') {
            return types::callable(
                parameters: $this->reflectCallableParameters($node->parameters),
                returnType: $this->reflect($node->returnType),
            );
        }

        if ($this->typeContext->resolveNameAsClass($node->identifier->name) === \Closure::class) {
            return types::closure(
                parameters: $this->reflectCallableParameters($node->parameters),
                returnType: $this->reflect($node->returnType),
            );
        }

        throw new DefaultReflectionException(sprintf('PhpDoc type "%s" is not supported.', $node));
    }

    /**
     * @param list<CallableTypeParameterNode> $nodes
     * @return list<Type\Parameter>
     */
    private function reflectCallableParameters(array $nodes): array
    {
        return array_map(
            fn(CallableTypeParameterNode $parameter): Type\Parameter => types::param(
                type: $this->reflect($parameter->type),
                hasDefault: $parameter->isOptional,
                variadic: $parameter->isVariadic,
            ),
            $nodes,
        );
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function reflectClassString(array $genericTypes): Type\Type
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return match (\count($genericTypes)) {
            0 => types::classString,
            1 => types::classString($this->reflect($genericTypes[0])),
            default => throw new DefaultReflectionException(),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectKeyOf(array $templateArguments): Type\KeyOfType
    {
        return match ($number = \count($templateArguments)) {
            1 => types::keyOf($this->reflect($templateArguments[0])),
            default => throw new DefaultReflectionException(sprintf('key-of type should have 1 argument, got %d.', $number)),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectValueOf(array $templateArguments): Type\ValueOfType
    {
        return match ($number = \count($templateArguments)) {
            1 => types::valueOf($this->reflect($templateArguments[0])),
            default => throw new DefaultReflectionException(sprintf('value-of type should have 1 argument, got %d.', $number)),
        };
    }

    private function reflectConditional(ConditionalTypeNode|ConditionalTypeForParameterNode $node): Type\ConditionalType
    {
        if ($node instanceof ConditionalTypeNode) {
            $subject = $this->reflect($node->subjectType);

            if (!$subject instanceof Type\TemplateType) {
                throw new DefaultReflectionException(sprintf('Conditional type subject should be an argument or a template, got %s.', $node->subjectType));
            }
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
