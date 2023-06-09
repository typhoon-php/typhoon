<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TReturn
 */
interface TypeVisitor
{
    /** @return TReturn */
    public function visitNever(Type\NeverType $type): mixed;

    /** @return TReturn */
    public function visitVoid(Type\VoidType $type): mixed;

    /** @return TReturn */
    public function visitNull(Type\NullType $type): mixed;

    /** @return TReturn */
    public function visitFalse(Type\FalseType $type): mixed;

    /** @return TReturn */
    public function visitTrue(Type\TrueType $type): mixed;

    /** @return TReturn */
    public function visitBool(Type\BoolType $type): mixed;

    /** @return TReturn */
    public function visitIntLiteral(Type\IntLiteralType $type): mixed;

    /** @return TReturn */
    public function visitLiteralInt(Type\LiteralIntType $type): mixed;

    /** @return TReturn */
    public function visitIntRange(Type\IntRangeType $type): mixed;

    /** @return TReturn */
    public function visitInt(Type\IntType $type): mixed;

    /** @return TReturn */
    public function visitFloatLiteral(Type\FloatLiteralType $type): mixed;

    /** @return TReturn */
    public function visitFloat(Type\FloatType $type): mixed;

    /** @return TReturn */
    public function visitStringLiteral(Type\StringLiteralType $type): mixed;

    /** @return TReturn */
    public function visitLiteralString(Type\LiteralStringType $type): mixed;

    /** @return TReturn */
    public function visitNumericString(Type\NumericStringType $type): mixed;

    /** @return TReturn */
    public function visitNamedClassString(Type\NamedClassStringType $type): mixed;

    /** @return TReturn */
    public function visitClassString(Type\ClassStringType $type): mixed;

    /** @return TReturn */
    public function visitCallableString(Type\CallableStringType $type): mixed;

    /** @return TReturn */
    public function visitInterfaceString(Type\InterfaceStringType $type): mixed;

    /** @return TReturn */
    public function visitEnumString(Type\EnumStringType $type): mixed;

    /** @return TReturn */
    public function visitTraitString(Type\TraitStringType $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyString(Type\NonEmptyStringType $type): mixed;

    /** @return TReturn */
    public function visitString(Type\StringType $type): mixed;

    /** @return TReturn */
    public function visitNumeric(Type\NumericType $type): mixed;

    /** @return TReturn */
    public function visitArrayKey(Type\ArrayKeyType $type): mixed;

    /** @return TReturn */
    public function visitScalar(Type\ScalarType $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyList(Type\NonEmptyListType $type): mixed;

    /** @return TReturn */
    public function visitList(Type\ListType $type): mixed;

    /** @return TReturn */
    public function visitShape(Type\ShapeType $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyArray(Type\NonEmptyArrayType $type): mixed;

    /** @return TReturn */
    public function visitCallableArray(Type\CallableArrayType $type): mixed;

    /** @return TReturn */
    public function visitArray(Type\ArrayType $type): mixed;

    /** @return TReturn */
    public function visitIterable(Type\IterableType $type): mixed;

    /** @return TReturn */
    public function visitNamedObject(Type\NamedObjectType $type): mixed;

    /** @return TReturn */
    public function visitStatic(Type\StaticType $type): mixed;

    /** @return TReturn */
    public function visitObject(Type\ObjectType $type): mixed;

    /** @return TReturn */
    public function visitResource(Type\ResourceType $type): mixed;

    /** @return TReturn */
    public function visitClosedResource(Type\ClosedResourceType $type): mixed;

    /** @return TReturn */
    public function visitClosure(Type\ClosureType $type): mixed;

    /** @return TReturn */
    public function visitCallable(Type\CallableType $type): mixed;

    /** @return TReturn */
    public function visitConstant(Type\ConstantType $type): mixed;

    /** @return TReturn */
    public function visitClassConstant(Type\ClassConstantType $type): mixed;

    /** @return TReturn */
    public function visitKeyOf(Type\KeyOfType $type): mixed;

    /** @return TReturn */
    public function visitValueOf(Type\ValueOfType $type): mixed;

    /** @return TReturn */
    public function visitFunctionTemplate(Type\FunctionTemplateType $type): mixed;

    /** @return TReturn */
    public function visitClassTemplate(Type\ClassTemplateType $type): mixed;

    /** @return TReturn */
    public function visitMethodTemplate(Type\MethodTemplateType $type): mixed;

    /** @return TReturn */
    public function visitIntersection(Type\IntersectionType $type): mixed;

    /** @return TReturn */
    public function visitUnion(Type\UnionType $type): mixed;

    /** @return TReturn */
    public function visitMixed(Type\MixedType $type): mixed;
}
