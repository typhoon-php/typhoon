<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Nullability;

use Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @implements Type\TypeVisitor<bool>
 */
final class NullableChecker implements Type\TypeVisitor
{
    private function __construct() {}

    public static function isNullable(?Type\Type $type): bool
    {
        return $type === null || $type->accept(new self());
    }

    public function visitNever(Type\NeverType $type): mixed
    {
        return false;
    }

    public function visitVoid(Type\VoidType $type): mixed
    {
        return false;
    }

    public function visitNull(Type\NullType $type): mixed
    {
        return true;
    }

    public function visitFalse(Type\FalseType $type): mixed
    {
        return false;
    }

    public function visitTrue(Type\TrueType $type): mixed
    {
        return false;
    }

    public function visitBool(Type\BoolType $type): mixed
    {
        return false;
    }

    public function visitIntLiteral(Type\IntLiteralType $type): mixed
    {
        return false;
    }

    public function visitAnyLiteralInt(Type\AnyLiteralIntType $type): mixed
    {
        return false;
    }

    public function visitIntRange(Type\IntRangeType $type): mixed
    {
        return false;
    }

    public function visitIntMask(Type\IntMaskType $type): mixed
    {
        return false;
    }

    public function visitIntMaskOf(Type\IntMaskOfType $type): mixed
    {
        return false;
    }

    public function visitInt(Type\IntType $type): mixed
    {
        return false;
    }

    public function visitFloatLiteral(Type\FloatLiteralType $type): mixed
    {
        return false;
    }

    public function visitFloat(Type\FloatType $type): mixed
    {
        return false;
    }

    public function visitStringLiteral(Type\StringLiteralType $type): mixed
    {
        return false;
    }

    public function visitAnyLiteralString(Type\AnyLiteralStringType $type): mixed
    {
        return false;
    }

    public function visitNumericString(Type\NumericStringType $type): mixed
    {
        return false;
    }

    public function visitClassStringLiteral(Type\ClassStringLiteralType $type): mixed
    {
        return false;
    }

    public function visitNamedClassString(Type\NamedClassStringType $type): mixed
    {
        return false;
    }

    public function visitClassString(Type\ClassStringType $type): mixed
    {
        return false;
    }

    public function visitCallableString(Type\CallableStringType $type): mixed
    {
        return false;
    }

    public function visitInterfaceString(Type\InterfaceStringType $type): mixed
    {
        return false;
    }

    public function visitEnumString(Type\EnumStringType $type): mixed
    {
        return false;
    }

    public function visitTraitString(Type\TraitStringType $type): mixed
    {
        return false;
    }

    public function visitNonEmptyString(Type\NonEmptyStringType $type): mixed
    {
        return false;
    }

    public function visitTruthyString(Type\TruthyStringType $type): mixed
    {
        return false;
    }

    public function visitString(Type\StringType $type): mixed
    {
        return false;
    }

    public function visitNumeric(Type\NumericType $type): mixed
    {
        return false;
    }

    public function visitArrayKey(Type\ArrayKeyType $type): mixed
    {
        return false;
    }

    public function visitScalar(Type\ScalarType $type): mixed
    {
        return false;
    }

    public function visitNonEmptyList(Type\NonEmptyListType $type): mixed
    {
        return false;
    }

    public function visitList(Type\ListType $type): mixed
    {
        return false;
    }

    public function visitArrayShape(Type\ArrayShapeType $type): mixed
    {
        return false;
    }

    public function visitNonEmptyArray(Type\NonEmptyArrayType $type): mixed
    {
        return false;
    }

    public function visitCallableArray(Type\CallableArrayType $type): mixed
    {
        return false;
    }

    public function visitArray(Type\ArrayType $type): mixed
    {
        return false;
    }

    public function visitIterable(Type\IterableType $type): mixed
    {
        return false;
    }

    public function visitNamedObject(Type\NamedObjectType $type): mixed
    {
        return false;
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        return false;
    }

    public function visitObjectShape(Type\ObjectShapeType $type): mixed
    {
        return false;
    }

    public function visitObject(Type\ObjectType $type): mixed
    {
        return false;
    }

    public function visitResource(Type\ResourceType $type): mixed
    {
        return false;
    }

    public function visitClosedResource(Type\ClosedResourceType $type): mixed
    {
        return false;
    }

    public function visitClosure(Type\ClosureType $type): mixed
    {
        return false;
    }

    public function visitCallable(Type\CallableType $type): mixed
    {
        return false;
    }

    public function visitConstant(Type\ConstantType $type): mixed
    {
        return false;
    }

    public function visitClassConstant(Type\ClassConstantType $type): mixed
    {
        return false;
    }

    public function visitKeyOf(Type\KeyOfType $type): mixed
    {
        return false;
    }

    public function visitValueOf(Type\ValueOfType $type): mixed
    {
        return false;
    }

    public function visitTemplate(Type\TemplateType $type): mixed
    {
        return false;
    }

    public function visitConditional(Type\ConditionalType $type): mixed
    {
        return false;
    }

    public function visitIntersection(Type\IntersectionType $type): mixed
    {
        return false;
    }

    public function visitUnion(Type\UnionType $type): mixed
    {
        foreach ($type->types as $type) {
            if ($type->accept($this)) {
                return true;
            }
        }

        return false;
    }

    public function visitMixed(Type\MixedType $type): mixed
    {
        return true;
    }
}
