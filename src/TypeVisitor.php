<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TReturn
 */
interface TypeVisitor
{
    /** @return TReturn */
    public function visitNever(NeverType $type): mixed;

    /** @return TReturn */
    public function visitVoid(VoidType $type): mixed;

    /** @return TReturn */
    public function visitNull(NullType $type): mixed;

    /** @return TReturn */
    public function visitNonEmpty(NonEmptyType $type): mixed;

    /** @return TReturn */
    public function visitLiteral(LiteralType $type): mixed;

    /** @return TReturn */
    public function visitAnyLiteral(AnyLiteralType $type): mixed;

    /** @return TReturn */
    public function visitClassStringLiteral(ClassStringLiteralType $type): mixed;

    /** @return TReturn */
    public function visitBool(BoolType $type): mixed;

    /** @return TReturn */
    public function visitIntRange(IntRangeType $type): mixed;

    /** @return TReturn */
    public function visitIntMask(IntMaskType $type): mixed;

    /** @return TReturn */
    public function visitIntMaskOf(IntMaskOfType $type): mixed;

    /** @return TReturn */
    public function visitInt(IntType $type): mixed;

    /** @return TReturn */
    public function visitFloat(FloatType $type): mixed;

    /** @return TReturn */
    public function visitNumericString(NumericStringType $type): mixed;

    /** @return TReturn */
    public function visitNamedClassString(NamedClassStringType $type): mixed;

    /** @return TReturn */
    public function visitClassString(ClassStringType $type): mixed;

    /** @return TReturn */
    public function visitTruthyString(TruthyStringType $type): mixed;

    /** @return TReturn */
    public function visitString(StringType $type): mixed;

    /** @return TReturn */
    public function visitList(ListType $type): mixed;

    /** @return TReturn */
    public function visitArrayShape(ArrayShapeType $type): mixed;

    /** @return TReturn */
    public function visitArray(ArrayType $type): mixed;

    /** @return TReturn */
    public function visitIterable(IterableType $type): mixed;

    /** @return TReturn */
    public function visitNamedObject(NamedObjectType $type): mixed;

    /** @return TReturn */
    public function visitStatic(StaticType $type): mixed;

    /** @return TReturn */
    public function visitObjectShape(ObjectShapeType $type): mixed;

    /** @return TReturn */
    public function visitObject(ObjectType $type): mixed;

    /** @return TReturn */
    public function visitResource(ResourceType $type): mixed;

    /** @return TReturn */
    public function visitClosure(ClosureType $type): mixed;

    /** @return TReturn */
    public function visitCallable(CallableType $type): mixed;

    /** @return TReturn */
    public function visitConstant(ConstantType $type): mixed;

    /** @return TReturn */
    public function visitClassConstant(ClassConstantType $type): mixed;

    /** @return TReturn */
    public function visitKeyOf(KeyOfType $type): mixed;

    /** @return TReturn */
    public function visitValueOf(ValueOfType $type): mixed;

    /** @return TReturn */
    public function visitOffset(OffsetType $type): mixed;

    /** @return TReturn */
    public function visitTemplate(TemplateType $type): mixed;

    /** @return TReturn */
    public function visitConditional(ConditionalType $type): mixed;

    /** @return TReturn */
    public function visitAlias(AliasType $type): mixed;

    /** @return TReturn */
    public function visitIntersection(IntersectionType $type): mixed;

    /** @return TReturn */
    public function visitUnion(UnionType $type): mixed;

    /** @return TReturn */
    public function visitMixed(MixedType $type): mixed;
}
