<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\DeclarationParser;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\Type\ShapeType;
use ExtendedTypeSystem\types;
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
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\DeclarationParser
 */
final class TypeParser
{
    /**
     * @return ($name is null ? null : class-string)
     */
    public static function nameToClassString(?Name $name): ?string
    {
        if ($name === null) {
            return null;
        }

        \assert($name->isFullyQualified());

        /** @var class-string */
        return $name->toString();
    }

    /**
     * @return ($typeNode is null ? null : Type)
     */
    public function parseNativeTypeNode(TypeScope $scope, null|Identifier|Name|ComplexType $typeNode): ?Type
    {
        if ($typeNode === null) {
            return null;
        }

        if ($typeNode instanceof Identifier) {
            return $this->parseIdentifierType($scope, $typeNode->name);
        }

        if ($typeNode instanceof Name) {
            return $this->parseName($scope, $typeNode);
        }

        if ($typeNode instanceof NullableType) {
            return types::nullable($this->parseNativeTypeNode($scope, $typeNode->type));
        }

        if ($typeNode instanceof UnionType) {
            return $this->parseUnionType(array_map(
                fn (Identifier|Name|ComplexType $childTypeNode): Type => $this->parseNativeTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof IntersectionType) {
            return $this->parseIntersectionType(array_map(
                fn (Identifier|Name|ComplexType $childTypeNode): Type => $this->parseNativeTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        throw new \LogicException(sprintf('Node %s is not supported.', get_debug_type($typeNode)));
    }

    /**
     * @return ($typeNode is null ? null : Type)
     */
    public function parsePHPDocTypeNode(TypeScope $scope, ?TypeNode $typeNode): ?Type
    {
        if ($typeNode === null) {
            return null;
        }

        if ($typeNode instanceof IdentifierTypeNode) {
            return $this->parseIdentifierType($scope, $typeNode->name);
        }

        if ($typeNode instanceof NullableTypeNode) {
            return types::nullable($this->parsePHPDocTypeNode($scope, $typeNode->type));
        }

        if ($typeNode instanceof UnionTypeNode) {
            return $this->parseUnionType(array_map(
                fn (TypeNode $childTypeNode): Type => $this->parsePHPDocTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof IntersectionTypeNode) {
            return $this->parseIntersectionType(array_map(
                fn (TypeNode $childTypeNode): Type => $this->parsePHPDocTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof ArrayTypeNode) {
            return types::array(valueType: $this->parsePHPDocTypeNode($scope, $typeNode->type));
        }

        if ($typeNode instanceof ArrayShapeNode) {
            return $this->parseArrayShapeNodeType($scope, $typeNode);
        }

        if ($typeNode instanceof ConstTypeNode) {
            return $this->parseConstTypeNodeType($scope, $typeNode);
        }

        if ($typeNode instanceof GenericTypeNode) {
            return $this->parseIdentifierType($scope, $typeNode->type->name, array_values($typeNode->genericTypes));
        }

        throw new \LogicException(sprintf('Unsupported PHPDoc type node %s.', $typeNode::class));
    }

    private function parseArrayShapeNodeType(TypeScope $scope, ArrayShapeNode $node): ShapeType
    {
        $elements = [];

        foreach ($node->items as $item) {
            $type = types::element(
                $this->parsePHPDocTypeNode($scope, $item->valueType),
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
                default => throw new \LogicException(sprintf('%s is not supported.', $keyName::class)),
            };

            $elements[$key] = $type;
        }

        return types::shape($elements);
    }

    private function parseConstTypeNodeType(TypeScope $scope, ConstTypeNode $typeNode): Type
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
            $class = self::nameToClassString($scope->resolveClassName(new Name($exprNode->className)));
            /** @var non-empty-string $exprNode->name */

            return types::classConstant($class, $exprNode->name);
        }

        throw new \LogicException(sprintf('Unsupported PHPDoc type node %s.', $exprNode::class));
    }

    /**
     * @param list<TypeNode> $genericTypes
     */
    private function parseIdentifierType(TypeScope $scope, string $name, array $genericTypes = []): Type
    {
        $atomicType = match ($name) {
            '' => throw new \LogicException('Name must not be empty.'),
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
                $this->parseIntRangeLimit($genericTypes[0] ?? null, 'min'),
                $this->parseIntRangeLimit($genericTypes[1] ?? null, 'max'),
            );
        }

        $templateArguments = array_map(
            fn (TypeNode $typeNode): Type => $this->parsePHPDocTypeNode($scope, $typeNode),
            $genericTypes,
        );

        if ($name === 'list') {
            return types::list($templateArguments[0] ?? types::mixed);
        }

        if ($name === 'non-empty-list') {
            return types::nonEmptyList($templateArguments[0] ?? types::mixed);
        }

        if ($name === 'array') {
            return types::array(...$this->parseArrayTemplateArguments($templateArguments));
        }

        if ($name === 'non-empty-array') {
            return types::nonEmptyArray(...$this->parseArrayTemplateArguments($templateArguments));
        }

        if ($name === 'iterable') {
            if (\count($templateArguments) <= 1) {
                return types::iterable(valueType: $templateArguments[0] ?? types::mixed);
            }

            return types::iterable($templateArguments[0], $templateArguments[1]);
        }

        return $this->parseName($scope, new Name($name), $templateArguments);
    }

    /**
     * @param list<Type> $templateArguments
     */
    private function parseName(TypeScope $scope, Name $nameNode, array $templateArguments = []): Type
    {
        /** @var non-empty-string */
        $name = $nameNode->toString();

        if ($nameNode instanceof Name\FullyQualified) {
            /** @var class-string $name */
            return types::object($name, ...$templateArguments);
        }

        if ($name[0] === '\\') {
            /** @var class-string */
            $name = ltrim($name, '\\');

            return types::object($name, ...$templateArguments);
        }

        if ($name === 'self') {
            return types::object($scope->self(), ...$templateArguments);
        }

        if ($name === 'parent') {
            return types::object($scope->parent(), ...$templateArguments);
        }

        if ($name === 'static') {
            if ($scope->isSelfFinal()) {
                return types::object($scope->self(), ...$templateArguments);
            }

            return types::static($scope->self(), ...$templateArguments);
        }

        $templateType = $scope->tryResolveTemplateType($name);

        if ($templateType !== null) {
            return $templateType;
        }

        $name = self::nameToClassString($scope->resolveClassName($nameNode));

        return types::object($name, ...$templateArguments);
    }

    /**
     * @param list<Type> $types
     */
    private function parseUnionType(array $types): Type
    {
        if ($types === []) {
            return types::mixed;
        }

        if (\count($types) === 1) {
            return $types[0];
        }

        return types::union(...$types);
    }

    /**
     * @param list<Type> $types
     */
    private function parseIntersectionType(array $types): Type
    {
        if ($types === []) {
            return types::mixed;
        }

        if (\count($types) === 1) {
            return $types[0];
        }

        return types::intersection(...$types);
    }

    /**
     * @param list<Type> $templateArguments
     * @return array{Type<array-key>, Type}
     */
    private function parseArrayTemplateArguments(array $templateArguments): array
    {
        if (\count($templateArguments) <= 1) {
            return [types::arrayKey, $templateArguments[0] ?? types::mixed];
        }

        /** @var array{Type<array-key>, Type} */
        return [$templateArguments[0], $templateArguments[1]];
    }

    private function parseIntRangeLimit(?TypeNode $typeNode, string $expectedIdentifier): ?int
    {
        if ($typeNode instanceof ConstTypeNode && $typeNode->constExpr instanceof ConstExprIntegerNode) {
            return (int) $typeNode->constExpr->value;
        }

        if ($typeNode instanceof IdentifierTypeNode && $typeNode->name === $expectedIdentifier) {
            return null;
        }

        throw new \LogicException(sprintf('Unexpected type %s for int range limit.', (string) $typeNode));
    }
}
