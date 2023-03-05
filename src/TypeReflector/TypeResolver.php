<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\Type\ArrayKeyType;
use ExtendedTypeSystem\Type\IntType;
use ExtendedTypeSystem\Type\ShapeType;
use ExtendedTypeSystem\Type\StringType;
use ExtendedTypeSystem\types;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
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
 * @psalm-internal ExtendedTypeSystem
 */
final class TypeResolver
{
    public function resolveTypeNode(Context $context, null|TypeNode|Identifier|Name|ComplexType $typeNode): ?Type
    {
        if ($typeNode === null) {
            return null;
        }

        if ($typeNode instanceof TypeNode) {
            return $this->resolvePHPDocTypeNode($context, $typeNode);
        }

        return $this->resolvePHPTypeNode($context, $typeNode);
    }

    private function resolvePHPTypeNode(Context $context, Identifier|Name|ComplexType $typeNode): Type
    {
        if ($typeNode instanceof Identifier) {
            return $this->resolveIdentifierType($context, $typeNode->name);
        }

        if ($typeNode instanceof Name) {
            return $this->resolveName($context, $typeNode);
        }

        if ($typeNode instanceof NullableType) {
            return types::nullable($this->resolvePHPTypeNode($context, $typeNode->type));
        }

        if ($typeNode instanceof UnionType) {
            return $this->resolveUnionType(array_map(
                fn (Identifier|Name|ComplexType $childTypeNode): Type => $this->resolvePHPTypeNode($context, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof IntersectionType) {
            return $this->resolveIntersectionType(array_map(
                fn (Identifier|Name|ComplexType $childTypeNode): Type => $this->resolvePHPTypeNode($context, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        throw new \LogicException(sprintf('Unknown type node %s.', $typeNode::class));
    }

    private function resolvePHPDocTypeNode(Context $context, TypeNode $typeNode): Type
    {
        if ($typeNode instanceof IdentifierTypeNode) {
            return $this->resolveIdentifierType($context, $typeNode->name);
        }

        if ($typeNode instanceof NullableTypeNode) {
            return types::nullable($this->resolvePHPDocTypeNode($context, $typeNode->type));
        }

        if ($typeNode instanceof UnionTypeNode) {
            return $this->resolveUnionType(array_map(
                fn (TypeNode $childTypeNode): Type => $this->resolvePHPDocTypeNode($context, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof IntersectionTypeNode) {
            return $this->resolveIntersectionType(array_map(
                fn (TypeNode $childTypeNode): Type => $this->resolvePHPDocTypeNode($context, $childTypeNode),
                array_values($typeNode->types),
            ));
        }

        if ($typeNode instanceof ArrayTypeNode) {
            return types::array(valueType: $this->resolvePHPDocTypeNode($context, $typeNode->type));
        }

        if ($typeNode instanceof ArrayShapeNode) {
            return $this->resolveArrayShapeNodeType($context, $typeNode);
        }

        if ($typeNode instanceof ConstTypeNode) {
            $exprNode = $typeNode->constExpr;

            // todo other const expr

            if ($exprNode instanceof ConstExprIntegerNode) {
                return types::intLiteral((int) $exprNode->value);
            }

            if ($exprNode instanceof ConstExprFloatNode) {
                return types::floatLiteral((float) $exprNode->value);
            }

            if ($exprNode instanceof ConstExprStringNode) {
                return types::stringLiteral($exprNode->value);
            }
        }

        if ($typeNode instanceof GenericTypeNode) {
            return $this->resolveIdentifierType(
                $context,
                $typeNode->type->name,
                array_values(array_map(
                    fn (TypeNode $typeNode): Type => $this->resolvePHPDocTypeNode($context, $typeNode),
                    $typeNode->genericTypes,
                )),
            );
        }

        throw new \LogicException(sprintf('Unknown type node %s.', $typeNode::class));
    }

    private function resolveArrayShapeNodeType(Context $context, ArrayShapeNode $node): ShapeType
    {
        $elements = [];

        foreach ($node->items as $item) {
            $type = $this->resolvePHPDocTypeNode($context, $item->valueType);

            if ($item->optional) {
                $type = types::optionalKey($type);
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

    /**
     * @param list<Type> $templateArguments
     */
    private function resolveIdentifierType(Context $context, string $name, array $templateArguments = []): Type
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
            'scalar' => types::scalar,
            'object' => types::object,
            'callable' => types::callable(),
            'mixed' => types::mixed,
            'void' => types::void,
            'never' => types::never,
            default => null
        };

        if ($atomicType !== null) {
            if ($templateArguments !== []) {
                throw new \LogicException('todo');
            }

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

        return $this->resolveName($context, new Name($name), $templateArguments);
    }

    /**
     * @todo validate class name?
     * @param list<Type> $templateArguments
     */
    private function resolveName(Context $context, Name $nameNode, array $templateArguments = []): Type
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
            return types::object($context->self(), ...$templateArguments);
        }

        if ($name === 'parent') {
            return types::object($context->parent(), ...$templateArguments);
        }

        if ($name === 'static') {
            return types::static($context->self(), ...$templateArguments);
        }

        $templateType = $context->tryResolveTemplateType($name);

        if ($templateType !== null) {
            if ($templateArguments !== []) {
                throw new \LogicException('todo');
            }

            return $templateType;
        }

        /** @var class-string */
        $name = $context->resolveName($nameNode)->toString();

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

        $keyType = $templateArguments[0];

        if (!($keyType instanceof ArrayKeyType || $keyType instanceof IntType || $keyType instanceof StringType)) {
            // todo add TypeStringifier
            throw new \LogicException(sprintf('Invalid array key type %s.', $keyType::class));
        }

        /** @var array{Type<array-key>, Type} */
        return [$keyType, $templateArguments[1]];
    }
}
