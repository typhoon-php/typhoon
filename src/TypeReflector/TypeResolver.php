<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

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
 * @psalm-internal ExtendedTypeSystem\TypeReflector
 */
final class TypeResolver
{
    public function resolveTypeNode(Scope $scope, null|TypeNode|Identifier|Name|ComplexType $typeNode): Type
    {
        if ($typeNode === null) {
            return types::mixed;
        }

        if ($typeNode instanceof TypeNode) {
            return $this->resolvePHPDocTypeNode($scope, $typeNode);
        }

        return $this->resolvePHPTypeNode($scope, $typeNode);
    }

    private function resolvePHPTypeNode(Scope $scope, Identifier|Name|ComplexType $typeNode): Type
    {
        if ($typeNode instanceof Identifier) {
            return $this->resolveIdentifierType($scope, $typeNode->name);
        }

        if ($typeNode instanceof Name) {
            return $this->resolveName($scope, $typeNode);
        }

        if ($typeNode instanceof NullableType) {
            return types::nullable($this->resolvePHPTypeNode($scope, $typeNode->type));
        }

        if ($typeNode instanceof UnionType) {
            return $this->resolveUnionType(array_map(
                fn (Identifier|Name|ComplexType $childTypeNode): Type => $this->resolvePHPTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof IntersectionType) {
            return $this->resolveIntersectionType(array_map(
                fn (Identifier|Name|ComplexType $childTypeNode): Type => $this->resolvePHPTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        throw new \LogicException(sprintf('Unknown type node %s.', $typeNode::class));
    }

    private function resolvePHPDocTypeNode(Scope $scope, TypeNode $typeNode): Type
    {
        if ($typeNode instanceof IdentifierTypeNode) {
            return $this->resolveIdentifierType($scope, $typeNode->name);
        }

        if ($typeNode instanceof NullableTypeNode) {
            return types::nullable($this->resolvePHPDocTypeNode($scope, $typeNode->type));
        }

        if ($typeNode instanceof UnionTypeNode) {
            return $this->resolveUnionType(array_map(
                fn (TypeNode $childTypeNode): Type => $this->resolvePHPDocTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof IntersectionTypeNode) {
            return $this->resolveIntersectionType(array_map(
                fn (TypeNode $childTypeNode): Type => $this->resolvePHPDocTypeNode($scope, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof ArrayTypeNode) {
            return types::array(valueType: $this->resolvePHPDocTypeNode($scope, $typeNode->type));
        }

        if ($typeNode instanceof ArrayShapeNode) {
            return $this->resolveArrayShapeNodeType($scope, $typeNode);
        }

        if ($typeNode instanceof ConstTypeNode) {
            return $this->resolveConstTypeNodeType($scope, $typeNode);
        }

        if ($typeNode instanceof GenericTypeNode) {
            return $this->resolveIdentifierType(
                $scope,
                $typeNode->type->name,
                array_values(array_map(
                    fn (TypeNode $typeNode): Type => $this->resolvePHPDocTypeNode($scope, $typeNode),
                    $typeNode->genericTypes,
                )),
            );
        }

        throw new \LogicException(sprintf('Unsupported PHPDoc type node %s.', $typeNode::class));
    }

    private function resolveArrayShapeNodeType(Scope $scope, ArrayShapeNode $node): ShapeType
    {
        $elements = [];

        foreach ($node->items as $item) {
            $type = $this->resolvePHPDocTypeNode($scope, $item->valueType);

            if ($item->optional) {
                $type = types::optional($type);
            }

            if ($item->keyName === null) {
                $elements[] = $type;

                continue;
            }

            $keyName = $item->keyName;

            $key = match ($keyName::class) {
                ConstExprIntegerNode::class => $keyName->value,
                ConstExprStringNode::class => $keyName->value,
                default => throw new \LogicException(sprintf('%s is not supported by %s.', $keyName::class, self::class)),
            };

            $elements[$key] = $type;
        }

        return types::shape($elements);
    }

    private function resolveConstTypeNodeType(Scope $scope, ConstTypeNode $typeNode): Type
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
            /** @var class-string */
            $class = $scope->resolveClassName(new Name($exprNode->className))->toString();
            /** @var non-empty-string $exprNode->name */

            return types::classConstant($class, $exprNode->name);
        }

        throw new \LogicException(sprintf('Unsupported PHPDoc type node %s.', $exprNode::class));
    }

    /**
     * @param list<Type> $templateArguments
     */
    private function resolveIdentifierType(Scope $scope, string $name, array $templateArguments = []): Type
    {
        $atomicType = match ($name) {
            '' => throw new \LogicException('Name must not be empty.'),
            'null' => types::null,
            'true' => types::true,
            'false' => types::false,
            'bool' => types::bool,
            'float' => types::float,
            'int' => types::int,
            'positive-int' => types::positiveInt,
            'negative-int' => types::negativeInt,
            'non-negative-int' => types::nonNegativeInt,
            'non-positive-int' => types::nonPositiveInt,
            'numeric' => types::numeric,
            'string' => types::string,
            'non-empty-string' => types::nonEmptyString,
            'numeric-string' => types::numericString,
            'array-key' => types::arrayKey,
            'scalar' => types::scalar,
            'object' => types::object,
            'callable' => types::callable(),
            'mixed' => types::mixed,
            'void' => types::void,
            'never' => types::never,
            default => null
        };

        if ($atomicType !== null) {
            return $atomicType;
        }

        if ($name === 'list') {
            return types::list($templateArguments[0] ?? types::mixed);
        }

        if ($name === 'non-empty-list') {
            return types::nonEmptyList($templateArguments[0] ?? types::mixed);
        }

        if ($name === 'array') {
            return types::array(...$this->resolveArrayTemplateArguments($templateArguments));
        }

        if ($name === 'non-empty-array') {
            return types::nonEmptyArray(...$this->resolveArrayTemplateArguments($templateArguments));
        }

        if ($name === 'iterable') {
            if (\count($templateArguments) <= 1) {
                return types::iterable(valueType: $templateArguments[0] ?? types::mixed);
            }

            return types::iterable($templateArguments[0], $templateArguments[1]);
        }

        return $this->resolveName($scope, new Name($name), $templateArguments);
    }

    /**
     * @param list<Type> $templateArguments
     */
    private function resolveName(Scope $scope, Name $nameNode, array $templateArguments = []): Type
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

        /** @var class-string */
        $name = $scope->resolveClassName($nameNode)->toString();

        return types::object($name, ...$templateArguments);
    }

    /**
     * @param list<Type> $types
     */
    private function resolveUnionType(array $types): Type
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
    private function resolveIntersectionType(array $types): Type
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
    private function resolveArrayTemplateArguments(array $templateArguments): array
    {
        if (\count($templateArguments) <= 1) {
            return [types::arrayKey, $templateArguments[0] ?? types::mixed];
        }

        /** @var array{Type<array-key>, Type} */
        return [$templateArguments[0], $templateArguments[1]];
    }
}
