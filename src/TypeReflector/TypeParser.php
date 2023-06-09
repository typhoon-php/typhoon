<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Reflection\TypeReflectionException;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\Type\ShapeType;
use ExtendedTypeSystem\types;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
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

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 */
final class TypeParser
{
    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct()
    {
    }

    /**
     * @return ($node is null ? null : Type)
     */
    public static function parseNativeType(Scope $scope, ?Node $node): ?Type
    {
        if ($node === null) {
            return null;
        }

        if ($node instanceof Identifier) {
            return self::parseIdentifier($scope, $node->name);
        }

        if ($node instanceof Name) {
            return self::parseName($scope, $node);
        }

        if ($node instanceof NullableType) {
            return types::nullable(self::parseNativeType($scope, $node->type));
        }

        if ($node instanceof UnionType) {
            return types::union(...array_map(
                fn (Identifier|Name|ComplexType $child): Type => self::parseNativeType($scope, $child),
                $node->types,
            ));
        }

        if ($node instanceof IntersectionType) {
            return types::intersection(...array_map(
                fn (Identifier|Name|ComplexType $child): Type => self::parseNativeType($scope, $child),
                $node->types,
            ));
        }

        throw new TypeReflectionException(sprintf('%s is not supported.', $node::class));
    }

    /**
     * @return ($node is null ? null : Type)
     */
    public static function parsePHPDocType(Scope $scope, ?TypeNode $node): ?Type
    {
        if ($node === null) {
            return null;
        }

        if ($node instanceof IdentifierTypeNode) {
            return self::parseIdentifier($scope, $node->name);
        }

        if ($node instanceof NullableTypeNode) {
            return types::nullable(self::parsePHPDocType($scope, $node->type));
        }

        if ($node instanceof UnionTypeNode) {
            return types::union(...array_map(
                fn (TypeNode $child): Type => self::parsePHPDocType($scope, $child),
                $node->types,
            ));
        }

        if ($node instanceof IntersectionTypeNode) {
            return types::intersection(...array_map(
                fn (TypeNode $child): Type => self::parsePHPDocType($scope, $child),
                $node->types,
            ));
        }

        if ($node instanceof ArrayTypeNode) {
            return types::array(valueType: self::parsePHPDocType($scope, $node->type));
        }

        if ($node instanceof ArrayShapeNode) {
            return self::parseArrayShape($scope, $node);
        }

        if ($node instanceof ConstTypeNode) {
            return self::parseConstExpr($scope, $node);
        }

        if ($node instanceof GenericTypeNode) {
            return self::parseIdentifier($scope, $node->type->name, $node->genericTypes);
        }

        if ($node instanceof CallableTypeNode) {
            return self::parseCallable($scope, $node);
        }

        throw new TypeReflectionException(sprintf('%s is not supported.', $node::class));
    }

    /**
     * @return ($reflectionType is null ? null : Type)
     */
    public static function parseReflectionType(Scope $scope, ?\ReflectionType $reflectionType): ?Type
    {
        if ($reflectionType === null) {
            return null;
        }

        if ($reflectionType instanceof \ReflectionNamedType) {
            $name = $reflectionType->getName();

            /** @psalm-suppress RedundantCondition */
            $type = $reflectionType->isBuiltin()
                ? self::parseIdentifier($scope, $name)
                : self::parseName($scope, new Name\FullyQualified($name));

            if ($name === 'null' || $name === 'mixed' || !$reflectionType->allowsNull()) {
                return $type;
            }

            return types::nullable($type);
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            return types::union(...array_map(
                fn (\ReflectionType $reflectionType): Type => self::parseReflectionType($scope, $reflectionType),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return types::intersection(...array_map(
                fn (\ReflectionType $reflectionType): Type => self::parseReflectionType($scope, $reflectionType),
                $reflectionType->getTypes(),
            ));
        }

        throw new TypeReflectionException(sprintf('%s is not supported.', $reflectionType::class));
    }

    private static function parseArrayShape(Scope $scope, ArrayShapeNode $node): ShapeType
    {
        $elements = [];

        foreach ($node->items as $item) {
            $type = types::element(
                self::parsePHPDocType($scope, $item->valueType),
                $item->optional,
            );

            if ($item->keyName === null) {
                $elements[] = $type;

                continue;
            }

            $keyName = $item->keyName;

            $key = match ($keyName::class) {
                ConstExprIntegerNode::class => $keyName->value,
                ConstExprStringNode::class => $keyName->value,
                IdentifierTypeNode::class => $keyName->name,
                default => throw new TypeReflectionException(sprintf('%s is not supported.', $keyName::class)),
            };

            $elements[$key] = $type;
        }

        return types::shape($elements, $node->sealed);
    }

    private static function parseConstExpr(Scope $scope, ConstTypeNode $typeNode): Type
    {
        $exprNode = $typeNode->constExpr;

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

            return types::classConstant(
                class: $scope->resolveClass(new Name($exprNode->className)),
                constant: $exprNode->name,
            );
        }

        throw new TypeReflectionException(sprintf('Unsupported PHPDoc type node %s.', $exprNode::class));
    }

    /**
     * @param non-empty-string $name
     * @param list<TypeNode> $genericTypes
     */
    private static function parseIdentifier(Scope $scope, string $name, array $genericTypes = []): Type
    {
        $atomicType = match ($name) {
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
            default => null
        };

        if ($atomicType !== null) {
            return $atomicType;
        }

        if ($name === 'int') {
            if ($genericTypes === []) {
                return types::int;
            }

            return types::int(
                self::parseIntRangeLimit($genericTypes[0] ?? null, 'min'),
                self::parseIntRangeLimit($genericTypes[1] ?? null, 'max'),
            );
        }

        $templateArguments = array_map(
            fn (TypeNode $typeNode): Type => self::parsePHPDocType($scope, $typeNode),
            $genericTypes,
        );

        if ($name === 'list') {
            return types::list($templateArguments[0] ?? types::mixed);
        }

        if ($name === 'non-empty-list') {
            return types::nonEmptyList($templateArguments[0] ?? types::mixed);
        }

        if ($name === 'array') {
            return types::array(...self::parseArrayTemplateArguments($templateArguments));
        }

        if ($name === 'non-empty-array') {
            return types::nonEmptyArray(...self::parseArrayTemplateArguments($templateArguments));
        }

        if ($name === 'iterable') {
            if (\count($templateArguments) <= 1) {
                return types::iterable(valueType: $templateArguments[0] ?? types::mixed);
            }

            return types::iterable($templateArguments[0], $templateArguments[1]);
        }

        return self::parseName($scope, self::nameFromString($name), $templateArguments);
    }

    /**
     * @param list<Type> $templateArguments
     */
    private static function parseName(Scope $scope, Name $name, array $templateArguments = []): Type
    {
        $nameAsString = $name->toString();

        $templateType = $scope->tryResolveTemplate($nameAsString);

        if ($templateType !== null) {
            return $templateType;
        }

        if ($nameAsString === 'static') {
            return types::static($scope->resolveClass(new Name(Scope::SELF)), ...$templateArguments);
        }

        return types::object($scope->resolveClass($name), ...$templateArguments);
    }

    /**
     * @param list<Type> $templateArguments
     * @return array{Type<array-key>, Type}
     */
    private static function parseArrayTemplateArguments(array $templateArguments): array
    {
        if (\count($templateArguments) <= 1) {
            return [types::arrayKey, $templateArguments[0] ?? types::mixed];
        }

        /** @var array{Type<array-key>, Type} */
        return [$templateArguments[0], $templateArguments[1]];
    }

    private static function parseIntRangeLimit(?TypeNode $typeNode, string $expectedIdentifier): ?int
    {
        if ($typeNode instanceof ConstTypeNode && $typeNode->constExpr instanceof ConstExprIntegerNode) {
            return (int) $typeNode->constExpr->value;
        }

        if ($typeNode instanceof IdentifierTypeNode && $typeNode->name === $expectedIdentifier) {
            return null;
        }

        throw new TypeReflectionException(sprintf('"%s" cannot be used as int range limit.', (string) $typeNode));
    }

    private static function parseCallable(Scope $scope, CallableTypeNode $node): Type
    {
        $parameters = array_map(
            static fn (CallableTypeParameterNode $parameter) => types::param(
                type: self::parsePHPDocType($scope, $parameter->type),
                hasDefault: $parameter->isOptional,
                variadic: $parameter->isVariadic,
            ),
            $node->parameters,
        );
        $returnType = self::parsePHPDocType($scope, $node->returnType);

        if ($node->identifier->name === 'callable') {
            return types::callable($parameters, $returnType);
        }

        $type = self::parseIdentifier($scope, $node->identifier->name);

        if ($type instanceof Type\NamedObjectType && $type->class === \Closure::class) {
            return types::closure($parameters, $returnType);
        }

        throw new TypeReflectionException(sprintf('"%s" cannot be used as callable type.', $node->identifier->name));
    }

    /**
     * @param non-empty-string $name
     */
    private static function nameFromString(string $name): Name
    {
        if ($name[0] === '\\') {
            return new Name\FullyQualified(substr($name, 1));
        }

        if (str_starts_with($name, 'namespace\\')) {
            return new Name\Relative(substr($name, 10));
        }

        return new Name($name);
    }
}
