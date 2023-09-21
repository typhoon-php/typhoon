<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Type;
use Typhoon\Type\types;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @implements TypeVisitor<Type\Type>
 */
final class ClassTemplateResolver implements TypeVisitor
{
    /**
     * @param class-string $class
     * @param non-empty-array<non-empty-string, Type\Type> $templateArguments
     */
    public function __construct(
        private readonly string $class,
        private readonly array $templateArguments,
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

    public function visitShape(Type\ShapeType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::shape(
            array_map(
                fn (Type\ShapeElement $element): Type\ShapeElement => types::element(
                    $element->type->accept($this),
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
        /** @psalm-suppress ImpureFunctionCall */
        return types::object($type->class, ...array_map(
            fn (Type\Type $type): Type\Type => $type->accept($this),
            $type->templateArguments,
        ));
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::static($type->declaringClass, ...array_map(
            fn (Type\Type $type): Type\Type => $type->accept($this),
            $type->templateArguments,
        ));
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
        /** @psalm-suppress ImpureFunctionCall */
        return types::closure(
            array_map(
                fn (Type\Parameter $parameter): Type\Parameter => types::param(
                    $parameter->type->accept($this),
                    $parameter->hasDefault,
                    $parameter->variadic,
                ),
                $type->parameters,
            ),
            $type->returnType?->accept($this),
        );
    }

    public function visitCallable(Type\CallableType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::callable(
            array_map(
                fn (Type\Parameter $parameter): Type\Parameter => types::param(
                    $parameter->type->accept($this),
                    $parameter->hasDefault,
                    $parameter->variadic,
                ),
                $type->parameters,
            ),
            $type->returnType?->accept($this),
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

    public function visitFunctionTemplate(Type\FunctionTemplateType $type): mixed
    {
        return $type;
    }

    public function visitClassTemplate(Type\ClassTemplateType $type): mixed
    {
        if ($type->class === $this->class && isset($this->templateArguments[$type->name])) {
            return $this->templateArguments[$type->name];
        }

        return $type;
    }

    public function visitMethodTemplate(Type\MethodTemplateType $type): mixed
    {
        return $type;
    }

    public function visitIntersection(Type\IntersectionType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::intersection(...array_map(
            fn (Type\Type $type): Type\Type => $type->accept($this),
            $type->types,
        ));
    }

    public function visitUnion(Type\UnionType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::union(...array_map(
            fn (Type\Type $type): Type\Type => $type->accept($this),
            $type->types,
        ));
    }

    public function visitMixed(Type\MixedType $type): mixed
    {
        return $type;
    }
}
