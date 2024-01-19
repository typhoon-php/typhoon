<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type;
use Typhoon\Type\ClassStringLiteralType;
use Typhoon\Type\ConditionalType;
use Typhoon\Type\IntMaskOfType;
use Typhoon\Type\IntMaskType;
use Typhoon\Type\ObjectShapeType;
use Typhoon\Type\TruthyString;
use Typhoon\Type\types;
use Typhoon\Type\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @implements TypeVisitor<Type\Type>
 */
final class TemplateResolver implements TypeVisitor
{
    /**
     * @param non-empty-array<non-empty-string, Type\Type> $templateArguments
     */
    private function __construct(
        private readonly array $templateArguments,
    ) {}

    /**
     * @psalm-pure
     * @psalm-suppress ImpurePropertyFetch
     * @param non-empty-array<TemplateReflection> $templates
     * @param array<Type\Type> $templateArguments
     */
    public static function create(array $templates, array $templateArguments): self
    {
        $resolvedTemplateArguments = [];

        foreach ($templates as $template) {
            $resolvedTemplateArguments[$template->name] = $templateArguments[$template->name]
                ?? $templateArguments[$template->getPosition()]
                ?? $template->getConstraint();
        }

        return new self($resolvedTemplateArguments);
    }

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

    public function visitIntMask(IntMaskType $type): mixed
    {
        return $type;
    }

    public function visitIntMaskOf(IntMaskOfType $type): mixed
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

    public function visitClassStringLiteral(ClassStringLiteralType $type): mixed
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

    public function visitTruthyString(TruthyString $type): mixed
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
        /** @psalm-suppress ImpureFunctionCall */
        return types::arrayShape(
            array_map(
                fn(Type\ArrayElement $element): Type\ArrayElement => types::arrayElement(
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
            fn(Type\Type $templateArgument): Type\Type => $templateArgument->accept($this),
            $type->templateArguments,
        ));
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::static(...array_map(
            fn(Type\Type $templateArgument): Type\Type => $templateArgument->accept($this),
            $type->templateArguments,
        ));
    }

    public function visitObjectShape(ObjectShapeType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::objectShape(
            array_map(
                fn(Type\Property $property): Type\Property => types::prop(
                    $property->type->accept($this),
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
        /** @psalm-suppress ImpureFunctionCall */
        return types::closure(
            array_map(
                fn(Type\Parameter $parameter): Type\Parameter => types::param(
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
                fn(Type\Parameter $parameter): Type\Parameter => types::param(
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

    public function visitTemplate(Type\TemplateType $type): mixed
    {
        return $this->templateArguments[$type->name] ?? $type;
    }

    public function visitConditional(ConditionalType $type): mixed
    {
        return types::conditional(
            $type->subject,
            $type->is->accept($this),
            $type->if->accept($this),
            $type->else->accept($this),
        );
    }

    public function visitIntersection(Type\IntersectionType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::intersection(...array_map(
            fn(Type\Type $part): Type\Type => $part->accept($this),
            $type->types,
        ));
    }

    public function visitUnion(Type\UnionType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return types::union(...array_map(
            fn(Type\Type $part): Type\Type => $part->accept($this),
            $type->types,
        ));
    }

    public function visitMixed(Type\MixedType $type): mixed
    {
        return $type;
    }
}
