<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @implements Type\TypeVisitor<Type\Type>
 * @psalm-suppress UnusedClass
 */
final class StaticTypeResolver implements Type\TypeVisitor
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {}

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
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return types::classString($type->type->accept($this));
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
        return types::nonEmptyList($type->valueType->accept($this));
    }

    public function visitList(Type\ListType $type): mixed
    {
        return types::list($type->valueType->accept($this));
    }

    public function visitArrayShape(Type\ArrayShapeType $type): mixed
    {
        $visitor = $this;

        return types::arrayShape(
            array_map(
                static fn(Type\ArrayElement $element): Type\ArrayElement => types::arrayElement(
                    $element->type->accept($visitor),
                    $element->optional,
                ),
                $type->elements,
            ),
            $type->sealed,
        );
    }

    public function visitNonEmptyArray(Type\NonEmptyArrayType $type): mixed
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return types::nonEmptyArray($type->keyType->accept($this), $type->valueType->accept($this));
    }

    public function visitCallableArray(Type\CallableArrayType $type): mixed
    {
        return $type;
    }

    public function visitArray(Type\ArrayType $type): mixed
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return types::array($type->keyType->accept($this), $type->valueType->accept($this));
    }

    public function visitIterable(Type\IterableType $type): mixed
    {
        return types::iterable($type->keyType->accept($this), $type->valueType->accept($this));
    }

    public function visitNamedObject(Type\NamedObjectType $type): mixed
    {
        $visitor = $this;

        return types::object($type->class, ...array_map(
            static fn(Type\Type $templateArgument): Type\Type => $templateArgument->accept($visitor),
            $type->templateArguments,
        ));
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        $visitor = $this;

        return types::object($this->class, ...array_map(
            static fn(Type\Type $templateArgument): Type\Type => $templateArgument->accept($visitor),
            $type->templateArguments,
        ));
    }

    public function visitObjectShape(Type\ObjectShapeType $type): mixed
    {
        $visitor = $this;

        return types::objectShape(
            array_map(
                static fn(Type\Property $property): Type\Property => types::prop(
                    $property->type->accept($visitor),
                    $property->optional,
                ),
                $type->properties,
            ),
        );
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
        $visitor = $this;

        return types::closure(
            array_map(
                static fn(Type\Parameter $parameter): Type\Parameter => types::param(
                    $parameter->type->accept($visitor),
                    $parameter->hasDefault,
                    $parameter->variadic,
                ),
                $type->parameters,
            ),
            $type->returnType?->accept($visitor),
        );
    }

    public function visitCallable(Type\CallableType $type): mixed
    {
        $visitor = $this;

        return types::callable(
            array_map(
                static fn(Type\Parameter $parameter): Type\Parameter => types::param(
                    $parameter->type->accept($visitor),
                    $parameter->hasDefault,
                    $parameter->variadic,
                ),
                $type->parameters,
            ),
            $type->returnType?->accept($visitor),
        );
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
        return types::keyOf($type->type->accept($this));
    }

    public function visitValueOf(Type\ValueOfType $type): mixed
    {
        return types::valueOf($type->type->accept($this));
    }

    public function visitTemplate(Type\TemplateType $type): mixed
    {
        return $type;
    }

    public function visitConditional(Type\ConditionalType $type): mixed
    {
        return types::conditional(
            $type->subject,
            $type->if->accept($this),
            $type->then->accept($this),
            $type->else->accept($this),
        );
    }

    public function visitIntersection(Type\IntersectionType $type): mixed
    {
        $visitor = $this;

        return types::intersection(...array_map(
            static fn(Type\Type $part): Type\Type => $part->accept($visitor),
            $type->types,
        ));
    }

    public function visitUnion(Type\UnionType $type): mixed
    {
        $visitor = $this;

        return types::union(...array_map(
            static fn(Type\Type $part): Type\Type => $part->accept($visitor),
            $type->types,
        ));
    }

    public function visitMixed(Type\MixedType $type): mixed
    {
        return $type;
    }
}
