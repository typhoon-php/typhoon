<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Type;

/**
 * @psalm-api
 * @psalm-immutable
 * @template TReturn
 */
interface TypeVisitor
{
    /** @return TReturn */
    public function visitNever(NeverT $type): mixed;

    /** @return TReturn */
    public function visitVoid(VoidT $type): mixed;

    /** @return TReturn */
    public function visitNull(NullT $type): mixed;

    /** @return TReturn */
    public function visitFalse(FalseT $type): mixed;

    /** @return TReturn */
    public function visitTrue(TrueT $type): mixed;

    /** @return TReturn */
    public function visitBool(BoolT $type): mixed;

    /** @return TReturn */
    public function visitIntLiteral(IntLiteralT $type): mixed;

    /** @return TReturn */
    public function visitLiteralInt(LiteralIntT $type): mixed;

    /** @return TReturn */
    public function visitIntRange(IntRangeT $type): mixed;

    /** @return TReturn */
    public function visitInt(IntT $type): mixed;

    /** @return TReturn */
    public function visitFloatLiteral(FloatLiteralT $type): mixed;

    /** @return TReturn */
    public function visitFloat(FloatT $type): mixed;

    /** @return TReturn */
    public function visitStringLiteral(StringLiteralT $type): mixed;

    /** @return TReturn */
    public function visitLiteralString(LiteralStringT $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyString(NonEmptyStringT $type): mixed;

    /** @return TReturn */
    public function visitNamedClassString(NamedClassStringT $type): mixed;

    /** @return TReturn */
    public function visitClassString(ClassStringT $type): mixed;

    /** @return TReturn */
    public function visitCallableString(CallableStringT $type): mixed;

    /** @return TReturn */
    public function visitInterfaceString(InterfaceStringT $type): mixed;

    /** @return TReturn */
    public function visitEnumString(EnumStringT $type): mixed;

    /** @return TReturn */
    public function visitTraitString(TraitStringT $type): mixed;

    /** @return TReturn */
    public function visitString(StringT $type): mixed;

    /** @return TReturn */
    public function visitNumeric(NumericT $type): mixed;

    /** @return TReturn */
    public function visitListShape(ListShapeT $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyList(NonEmptyListT $type): mixed;

    /** @return TReturn */
    public function visitList(ListT $type): mixed;

    /** @return TReturn */
    public function visitArrayShape(ArrayShapeT $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyArray(NonEmptyArrayT $type): mixed;

    /** @return TReturn */
    public function visitCallableArray(CallableArrayT $type): mixed;

    /** @return TReturn */
    public function visitArray(ArrayT $type): mixed;

    /** @return TReturn */
    public function visitIterable(IterableT $type): mixed;

    /** @return TReturn */
    public function visitNamedObject(NamedObjectT $type): mixed;

    /** @return TReturn */
    public function visitStatic(StaticT $type): mixed;

    /** @return TReturn */
    public function visitObject(ObjectT $type): mixed;

    /** @return TReturn */
    public function visitResource(ResourceT $type): mixed;

    /** @return TReturn */
    public function visitClosedResource(ClosedResourceT $type): mixed;

    /** @return TReturn */
    public function visitClosure(ClosureT $type): mixed;

    /** @return TReturn */
    public function visitCallable(CallableT $type): mixed;

    /** @return TReturn */
    public function visitConstant(ConstantT $type): mixed;

    /** @return TReturn */
    public function visitClassConstant(ClassConstantT $type): mixed;

    /** @return TReturn */
    public function visitKeyOf(KeyOfT $type): mixed;

    /** @return TReturn */
    public function visitValueOf(ValueOfT $type): mixed;

    /** @return TReturn */
    public function visitTemplate(TemplateT $type): mixed;

    /** @return TReturn */
    public function visitIntersection(IntersectionT $type): mixed;

    /** @return TReturn */
    public function visitUnion(UnionT $type): mixed;

    /** @return TReturn */
    public function visitMixed(MixedT $type): mixed;

    /** @return TReturn */
    public function visitAlias(TypeAlias $type): mixed;
}
