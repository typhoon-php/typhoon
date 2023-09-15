<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

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
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Typhoon\Reflection\NameResolution\NameAsTypeResolver;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Type;
use Typhoon\Type\ShapeType;
use Typhoon\types;
use Typhoon\TypeStringifier;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDocTypeReflector
{
    /**
     * @param callable(non-empty-string): bool $classExists
     */
    private function __construct(
        private readonly NameContext $nameContext,
        private $classExists,
    ) {}

    /**
     * @param callable(non-empty-string): bool $classExists
     */
    public static function reflect(NameContext $nameContext, callable $classExists, TypeNode $typeNode): Type
    {
        return (new self($nameContext, $classExists))->doReflect($typeNode);
    }

    private function doReflect(TypeNode $node): Type
    {
        if ($node instanceof NullableTypeNode) {
            return types::nullable($this->doReflect($node->type));
        }

        if ($node instanceof UnionTypeNode) {
            return types::union(...array_map($this->doReflect(...), $node->types));
        }

        if ($node instanceof IntersectionTypeNode) {
            return types::intersection(...array_map($this->doReflect(...), $node->types));
        }

        if ($node instanceof ArrayTypeNode) {
            return types::array(valueType: $this->doReflect($node->type));
        }

        if ($node instanceof ArrayShapeNode) {
            return $this->reflectArrayShape($node);
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

        throw new ReflectionException(sprintf('Type node %s is not supported.', $node::class));
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function reflectIdentifier(string $name, array $genericTypes = []): Type
    {
        if ($name === 'int') {
            return $this->reflectInt($genericTypes);
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

        return match ($name) {
            'null' => types::null,
            'true' => types::true,
            'false' => types::false,
            'bool' => types::bool,
            'float' => types::float,
            'positive-int' => types::positiveInt(),
            'negative-int' => types::negativeInt(),
            'non-negative-int' => types::nonNegativeInt(),
            'non-positive-int' => types::nonPositiveInt(),
            'numeric' => types::numeric,
            'string' => types::string,
            'non-empty-string' => types::nonEmptyString,
            'numeric-string' => types::numericString,
            'array-key' => types::arrayKey,
            'literal-int' => types::literalInt,
            'literal-string' => types::literalString,
            'class-string' => types::classString,
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
    private function reflectName(string $name, array $genericTypes): Type
    {
        $type = $this->nameContext->resolveName($name, new NameAsTypeResolver(
            classExists: $this->classExists,
            templateArguments: array_map($this->doReflect(...), $genericTypes),
        ));

        if ($type instanceof Type\NamedObjectType && $type->class === \Closure::class) {
            return types::closure();
        }

        return $type;
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectInt(array $templateArguments): Type
    {
        return match (\count($templateArguments)) {
            0 => types::int,
            2 => types::int(
                min: $this->reflectIntLimit($templateArguments[0], 'min'),
                max: $this->reflectIntLimit($templateArguments[1], 'max'),
            ),
            default => throw new \LogicException(),
        };
    }

    private function reflectIntLimit(TypeNode $type, string $unlimitedName): ?int
    {
        if ($type instanceof IdentifierTypeNode && $type->name === $unlimitedName) {
            return null;
        }

        $type = $this->doReflect($type);

        if ($type instanceof Type\IntLiteralType) {
            return $type->value;
        }

        throw new ReflectionException(sprintf('%s cannot be used as int range limit.', TypeStringifier::stringify($type)));
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectList(array $templateArguments): Type
    {
        return match (\count($templateArguments)) {
            0 => types::list(),
            1 => types::list($this->doReflect($templateArguments[0])),
            default => throw new \LogicException(),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectNonEmptyList(array $templateArguments): Type
    {
        return match (\count($templateArguments)) {
            0 => types::nonEmptyList(),
            1 => types::nonEmptyList($this->doReflect($templateArguments[0])),
            default => throw new \LogicException(),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectArray(array $templateArguments): Type
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @todo check array-key
         */
        return match (\count($templateArguments)) {
            0 => types::array(),
            1 => types::array(valueType: $this->doReflect($templateArguments[0])),
            2 => types::array(...array_map($this->doReflect(...), $templateArguments)),
            default => throw new \LogicException(),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectNonEmptyArray(array $templateArguments): Type
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @todo check array-key
         */
        return match (\count($templateArguments)) {
            0 => types::nonEmptyArray(),
            1 => types::nonEmptyArray(valueType: $this->doReflect($templateArguments[0])),
            2 => types::nonEmptyArray(...array_map($this->doReflect(...), $templateArguments)),
            default => throw new \LogicException(),
        };
    }

    /**
     * @param list<TypeNode> $templateArguments
     */
    private function reflectIterable(array $templateArguments): Type
    {
        return match (\count($templateArguments)) {
            0 => types::iterable(),
            1 => types::iterable(valueType: $this->doReflect($templateArguments[0])),
            2 => types::iterable(...array_map($this->doReflect(...), $templateArguments)),
            default => throw new \LogicException(),
        };
    }

    private function reflectArrayShape(ArrayShapeNode $node): ShapeType
    {
        $elements = [];

        foreach ($node->items as $item) {
            $type = types::element($this->doReflect($item->valueType), $item->optional);

            if ($item->keyName === null) {
                $elements[] = $type;

                continue;
            }

            $keyName = $item->keyName;

            $key = match ($keyName::class) {
                ConstExprIntegerNode::class => $keyName->value,
                ConstExprStringNode::class => $keyName->value,
                IdentifierTypeNode::class => $keyName->name,
                default => throw new ReflectionException(sprintf('%s is not supported.', $keyName::class)),
            };

            $elements[$key] = $type;
        }

        return types::shape($elements, $node->sealed);
    }

    private function reflectConstExpr(ConstTypeNode $node): Type
    {
        $exprNode = $node->constExpr;

        if ($exprNode instanceof ConstExprIntegerNode) {
            return types::intLiteral((int) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprFloatNode) {
            return types::floatLiteral((float) $exprNode->value);
        }

        if ($exprNode instanceof ConstExprStringNode) {
            return types::stringLiteral($exprNode->value);
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

            $class = $this->nameContext->resolveNameAsClass($exprNode->className);

            return types::classConstant($class, $exprNode->name);
        }

        throw new ReflectionException(sprintf('PhpDoc node %s is not supported.', $exprNode::class));
    }

    private function reflectCallable(CallableTypeNode $node): Type
    {
        if ($node->identifier->name === 'callable') {
            return types::callable(
                parameters: $this->reflectCallableParameters($node->parameters),
                returnType: $this->doReflect($node->returnType),
            );
        }

        if ($this->nameContext->resolveNameAsClass($node->identifier->name) === \Closure::class) {
            return types::closure(
                parameters: $this->reflectCallableParameters($node->parameters),
                returnType: $this->doReflect($node->returnType),
            );
        }

        throw new ReflectionException(sprintf('PhpDoc type "%s" is not supported.', (string) $node));
    }

    /**
     * @param list<CallableTypeParameterNode> $nodes
     * @return list<Type\Parameter>
     */
    private function reflectCallableParameters(array $nodes): array
    {
        return array_map(
            fn (CallableTypeParameterNode $parameter): Type\Parameter => types::param(
                type: $this->doReflect($parameter->type),
                hasDefault: $parameter->isOptional,
                variadic: $parameter->isVariadic,
            ),
            $nodes,
        );
    }
}
