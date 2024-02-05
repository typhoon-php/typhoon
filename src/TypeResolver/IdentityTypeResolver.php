<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @implements Type\TypeVisitor<Type\Type>
 */
final class IdentityTypeResolver implements Type\TypeVisitor
{
    public function visitNever(Type\NeverType $type): mixed
    {
        return $type;
    }

    public function visitVoid(Type\VoidType $type): mixed
    {
        return $type;
    }

    public function visitNull(Type\NullType $type): mixed
    {
        return $type;
    }

    public function visitFalse(Type\FalseType $type): mixed
    {
        return $type;
    }

    public function visitTrue(Type\TrueType $type): mixed
    {
        return $type;
    }

    public function visitBool(Type\BoolType $type): mixed
    {
        return $type;
    }

    public function visitIntLiteral(Type\IntLiteralType $type): mixed
    {
        return $type;
    }

    public function visitLiteralInt(Type\LiteralIntType $type): mixed
    {
        return $type;
    }

    public function visitIntRange(Type\IntRangeType $type): mixed
    {
        return $type;
    }

    public function visitIntMask(Type\IntMaskType $type): mixed
    {
        return $type;
    }

    public function visitIntMaskOf(Type\IntMaskOfType $type): mixed
    {
        return $type;
    }

    public function visitInt(Type\IntType $type): mixed
    {
        return $type;
    }

    public function visitFloatLiteral(Type\FloatLiteralType $type): mixed
    {
        return $type;
    }

    public function visitFloat(Type\FloatType $type): mixed
    {
        return $type;
    }

    public function visitStringLiteral(Type\StringLiteralType $type): mixed
    {
        return $type;
    }

    public function visitLiteralString(Type\LiteralStringType $type): mixed
    {
        return $type;
    }

    public function visitNumericString(Type\NumericStringType $type): mixed
    {
        return $type;
    }

    public function visitClassStringLiteral(Type\ClassStringLiteralType $type): mixed
    {
        return $type;
    }

    public function visitNamedClassString(Type\NamedClassStringType $type): mixed
    {
        return $type;
    }

    public function visitClassString(Type\ClassStringType $type): mixed
    {
        return $type;
    }

    public function visitCallableString(Type\CallableStringType $type): mixed
    {
        return $type;
    }

    public function visitInterfaceString(Type\InterfaceStringType $type): mixed
    {
        return $type;
    }

    public function visitEnumString(Type\EnumStringType $type): mixed
    {
        return $type;
    }

    public function visitTraitString(Type\TraitStringType $type): mixed
    {
        return $type;
    }

    public function visitNonEmptyString(Type\NonEmptyStringType $type): mixed
    {
        return $type;
    }

    public function visitTruthyString(Type\TruthyStringType $type): mixed
    {
        return $type;
    }

    public function visitString(Type\StringType $type): mixed
    {
        return $type;
    }

    public function visitNumeric(Type\NumericType $type): mixed
    {
        return $type;
    }

    public function visitArrayKey(Type\ArrayKeyType $type): mixed
    {
        return $type;
    }

    public function visitScalar(Type\ScalarType $type): mixed
    {
        return $type;
    }

    public function visitNonEmptyList(Type\NonEmptyListType $type): mixed
    {
        return $type;
    }

    public function visitList(Type\ListType $type): mixed
    {
        return $type;
    }

    public function visitArrayShape(Type\ArrayShapeType $type): mixed
    {
        return $type;
    }

    public function visitNonEmptyArray(Type\NonEmptyArrayType $type): mixed
    {
        return $type;
    }

    public function visitCallableArray(Type\CallableArrayType $type): mixed
    {
        return $type;
    }

    public function visitArray(Type\ArrayType $type): mixed
    {
        return $type;
    }

    public function visitIterable(Type\IterableType $type): mixed
    {
        return $type;
    }

    public function visitNamedObject(Type\NamedObjectType $type): mixed
    {
        return $type;
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        return $type;
    }

    public function visitObjectShape(Type\ObjectShapeType $type): mixed
    {
        return $type;
    }

    public function visitObject(Type\ObjectType $type): mixed
    {
        return $type;
    }

    public function visitResource(Type\ResourceType $type): mixed
    {
        return $type;
    }

    public function visitClosedResource(Type\ClosedResourceType $type): mixed
    {
        return $type;
    }

    public function visitClosure(Type\ClosureType $type): mixed
    {
        return $type;
    }

    public function visitCallable(Type\CallableType $type): mixed
    {
        return $type;
    }

    public function visitConstant(Type\ConstantType $type): mixed
    {
        return $type;
    }

    public function visitClassConstant(Type\ClassConstantType $type): mixed
    {
        return $type;
    }

    public function visitKeyOf(Type\KeyOfType $type): mixed
    {
        return $type;
    }

    public function visitValueOf(Type\ValueOfType $type): mixed
    {
        return $type;
    }

    public function visitTemplate(Type\TemplateType $type): mixed
    {
        return $type;
    }

    public function visitConditional(Type\ConditionalType $type): mixed
    {
        return $type;
    }

    public function visitIntersection(Type\IntersectionType $type): mixed
    {
        return $type;
    }

    public function visitUnion(Type\UnionType $type): mixed
    {
        return $type;
    }

    public function visitMixed(Type\MixedType $type): mixed
    {
        return $type;
    }
}
