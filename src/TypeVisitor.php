<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Type\ArrayShapeT;
use ExtendedTypeSystem\Type\ArrayT;
use ExtendedTypeSystem\Type\BoolT;
use ExtendedTypeSystem\Type\CallableArrayT;
use ExtendedTypeSystem\Type\CallableStringT;
use ExtendedTypeSystem\Type\CallableT;
use ExtendedTypeSystem\Type\ClassConstantT;
use ExtendedTypeSystem\Type\ClassStringT;
use ExtendedTypeSystem\Type\ClosedResourceT;
use ExtendedTypeSystem\Type\ClosureT;
use ExtendedTypeSystem\Type\ConstantT;
use ExtendedTypeSystem\Type\EnumStringT;
use ExtendedTypeSystem\Type\FalseT;
use ExtendedTypeSystem\Type\FloatLiteralT;
use ExtendedTypeSystem\Type\FloatT;
use ExtendedTypeSystem\Type\InterfaceStringT;
use ExtendedTypeSystem\Type\IntersectionT;
use ExtendedTypeSystem\Type\IntLiteralT;
use ExtendedTypeSystem\Type\IntRangeT;
use ExtendedTypeSystem\Type\IntT;
use ExtendedTypeSystem\Type\IterableT;
use ExtendedTypeSystem\Type\KeyOfT;
use ExtendedTypeSystem\Type\ListT;
use ExtendedTypeSystem\Type\LiteralIntT;
use ExtendedTypeSystem\Type\LiteralStringT;
use ExtendedTypeSystem\Type\MixedT;
use ExtendedTypeSystem\Type\NamedClassStringT;
use ExtendedTypeSystem\Type\NamedObjectT;
use ExtendedTypeSystem\Type\NeverT;
use ExtendedTypeSystem\Type\NonEmptyArrayT;
use ExtendedTypeSystem\Type\NonEmptyListT;
use ExtendedTypeSystem\Type\NonEmptyStringT;
use ExtendedTypeSystem\Type\NullT;
use ExtendedTypeSystem\Type\NumericT;
use ExtendedTypeSystem\Type\ObjectT;
use ExtendedTypeSystem\Type\ResourceT;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\StringLiteralT;
use ExtendedTypeSystem\Type\StringT;
use ExtendedTypeSystem\Type\TemplateT;
use ExtendedTypeSystem\Type\TraitStringT;
use ExtendedTypeSystem\Type\TrueT;
use ExtendedTypeSystem\Type\UnionT;
use ExtendedTypeSystem\Type\ValueOfT;
use ExtendedTypeSystem\Type\VoidT;

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
