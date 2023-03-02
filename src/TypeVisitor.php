<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TReturn
 */
interface TypeVisitor
{
    /** @return TReturn */
    public function visitNever(Type\NeverT $type): mixed;

    /** @return TReturn */
    public function visitVoid(Type\VoidT $type): mixed;

    /** @return TReturn */
    public function visitNull(Type\NullT $type): mixed;

    /** @return TReturn */
    public function visitFalse(Type\FalseT $type): mixed;

    /** @return TReturn */
    public function visitTrue(Type\TrueT $type): mixed;

    /** @return TReturn */
    public function visitBool(Type\BoolT $type): mixed;

    /** @return TReturn */
    public function visitIntLiteral(Type\IntLiteralT $type): mixed;

    /** @return TReturn */
    public function visitLiteralInt(Type\LiteralIntT $type): mixed;

    /** @return TReturn */
    public function visitIntRange(Type\IntRangeT $type): mixed;

    /** @return TReturn */
    public function visitInt(Type\IntT $type): mixed;

    /** @return TReturn */
    public function visitFloatLiteral(Type\FloatLiteralT $type): mixed;

    /** @return TReturn */
    public function visitFloat(Type\FloatT $type): mixed;

    /** @return TReturn */
    public function visitStringLiteral(Type\StringLiteralT $type): mixed;

    /** @return TReturn */
    public function visitLiteralString(Type\LiteralStringT $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyString(Type\NonEmptyStringT $type): mixed;

    /** @return TReturn */
    public function visitNamedClassString(Type\NamedClassStringT $type): mixed;

    /** @return TReturn */
    public function visitClassString(Type\ClassStringT $type): mixed;

    /** @return TReturn */
    public function visitCallableString(Type\CallableStringT $type): mixed;

    /** @return TReturn */
    public function visitInterfaceString(Type\InterfaceStringT $type): mixed;

    /** @return TReturn */
    public function visitEnumString(Type\EnumStringT $type): mixed;

    /** @return TReturn */
    public function visitTraitString(Type\TraitStringT $type): mixed;

    /** @return TReturn */
    public function visitString(Type\StringT $type): mixed;

    /** @return TReturn */
    public function visitNumeric(Type\NumericT $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyList(Type\NonEmptyListT $type): mixed;

    /** @return TReturn */
    public function visitList(Type\ListT $type): mixed;

    /** @return TReturn */
    public function visitArrayShape(Type\ArrayShapeT $type): mixed;

    /** @return TReturn */
    public function visitNonEmptyArray(Type\NonEmptyArrayT $type): mixed;

    /** @return TReturn */
    public function visitCallableArray(Type\CallableArrayT $type): mixed;

    /** @return TReturn */
    public function visitArray(Type\ArrayT $type): mixed;

    /** @return TReturn */
    public function visitIterable(Type\IterableT $type): mixed;

    /** @return TReturn */
    public function visitNamedObject(Type\NamedObjectT $type): mixed;

    /** @return TReturn */
    public function visitStatic(Type\StaticT $type): mixed;

    /** @return TReturn */
    public function visitObject(Type\ObjectT $type): mixed;

    /** @return TReturn */
    public function visitResource(Type\ResourceT $type): mixed;

    /** @return TReturn */
    public function visitClosedResource(Type\ClosedResourceT $type): mixed;

    /** @return TReturn */
    public function visitClosure(Type\ClosureT $type): mixed;

    /** @return TReturn */
    public function visitCallable(Type\CallableT $type): mixed;

    /** @return TReturn */
    public function visitConstant(Type\ConstantT $type): mixed;

    /** @return TReturn */
    public function visitClassConstant(Type\ClassConstantT $type): mixed;

    /** @return TReturn */
    public function visitKeyOf(Type\KeyOfT $type): mixed;

    /** @return TReturn */
    public function visitValueOf(Type\ValueOfT $type): mixed;

    /** @return TReturn */
    public function visitTemplate(Type\TemplateT $type): mixed;

    /** @return TReturn */
    public function visitIntersection(Type\IntersectionT $type): mixed;

    /** @return TReturn */
    public function visitUnion(Type\UnionT $type): mixed;

    /** @return TReturn */
    public function visitMixed(Type\MixedT $type): mixed;

    /** @return TReturn */
    public function visitAlias(TypeAlias $type): mixed;
}
