<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Type\ArrayKeyType;
use ExtendedTypeSystem\Type\ArrayType;
use ExtendedTypeSystem\Type\BoolType;
use ExtendedTypeSystem\Type\CallableArrayType;
use ExtendedTypeSystem\Type\CallableStringType;
use ExtendedTypeSystem\Type\CallableType;
use ExtendedTypeSystem\Type\ClassConstantType;
use ExtendedTypeSystem\Type\ClassStringType;
use ExtendedTypeSystem\Type\ClassTemplateType;
use ExtendedTypeSystem\Type\ClosedResourceType;
use ExtendedTypeSystem\Type\ClosureType;
use ExtendedTypeSystem\Type\ConstantType;
use ExtendedTypeSystem\Type\EnumStringType;
use ExtendedTypeSystem\Type\FalseType;
use ExtendedTypeSystem\Type\FloatLiteralType;
use ExtendedTypeSystem\Type\FloatType;
use ExtendedTypeSystem\Type\FunctionTemplateType;
use ExtendedTypeSystem\Type\InterfaceStringType;
use ExtendedTypeSystem\Type\IntersectionType;
use ExtendedTypeSystem\Type\IntLiteralType;
use ExtendedTypeSystem\Type\IntRangeType;
use ExtendedTypeSystem\Type\IntType;
use ExtendedTypeSystem\Type\IterableType;
use ExtendedTypeSystem\Type\KeyOfType;
use ExtendedTypeSystem\Type\ListType;
use ExtendedTypeSystem\Type\LiteralIntType;
use ExtendedTypeSystem\Type\LiteralStringType;
use ExtendedTypeSystem\Type\MethodTemplateType;
use ExtendedTypeSystem\Type\MixedType;
use ExtendedTypeSystem\Type\NamedClassStringType;
use ExtendedTypeSystem\Type\NamedObjectType;
use ExtendedTypeSystem\Type\NeverType;
use ExtendedTypeSystem\Type\NonEmptyArrayType;
use ExtendedTypeSystem\Type\NonEmptyListType;
use ExtendedTypeSystem\Type\NonEmptyStringType;
use ExtendedTypeSystem\Type\NullType;
use ExtendedTypeSystem\Type\NumericStringType;
use ExtendedTypeSystem\Type\NumericType;
use ExtendedTypeSystem\Type\ObjectType;
use ExtendedTypeSystem\Type\Parameter;
use ExtendedTypeSystem\Type\ResourceType;
use ExtendedTypeSystem\Type\ScalarType;
use ExtendedTypeSystem\Type\ShapeElement;
use ExtendedTypeSystem\Type\ShapeType;
use ExtendedTypeSystem\Type\StaticType;
use ExtendedTypeSystem\Type\StringLiteralType;
use ExtendedTypeSystem\Type\StringType;
use ExtendedTypeSystem\Type\TraitStringType;
use ExtendedTypeSystem\Type\TrueType;
use ExtendedTypeSystem\Type\UnionType;
use ExtendedTypeSystem\Type\ValueOfType;
use ExtendedTypeSystem\Type\VoidType;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements TypeVisitor<non-empty-string>
 * @psalm-suppress ImpureFunctionCall
 */
final class TypeStringifier implements TypeVisitor
{
    private function __construct()
    {
    }

    /**
     * @return non-empty-string
     */
    public static function stringify(Type $type): string
    {
        return $type->accept(new self());
    }

    public function visitNever(NeverType $type): mixed
    {
        return 'never';
    }

    public function visitVoid(VoidType $type): mixed
    {
        return 'void';
    }

    public function visitNull(NullType $type): mixed
    {
        return 'null';
    }

    public function visitFalse(FalseType $type): mixed
    {
        return 'false';
    }

    public function visitTrue(TrueType $type): mixed
    {
        return 'true';
    }

    public function visitBool(BoolType $type): mixed
    {
        return 'bool';
    }

    public function visitIntLiteral(IntLiteralType $type): mixed
    {
        return (string) $type->value;
    }

    public function visitLiteralInt(LiteralIntType $type): mixed
    {
        return 'literal-int';
    }

    public function visitIntRange(IntRangeType $type): mixed
    {
        if ($type->min === null && $type->max === null) {
            return 'int';
        }

        return sprintf('int<%s, %s>', $type->min ?? 'min', $type->max ?? 'max');
    }

    public function visitInt(IntType $type): mixed
    {
        return 'int';
    }

    public function visitFloatLiteral(FloatLiteralType $type): mixed
    {
        return (string) $type->value;
    }

    public function visitFloat(FloatType $type): mixed
    {
        return 'float';
    }

    public function visitStringLiteral(StringLiteralType $type): mixed
    {
        return $this->escapeStringLiteral($type->value);
    }

    public function visitLiteralString(LiteralStringType $type): mixed
    {
        return 'literal-string';
    }

    public function visitNumericString(NumericStringType $type): mixed
    {
        return 'numeric-string';
    }

    public function visitNamedClassString(NamedClassStringType $type): mixed
    {
        return sprintf('class-string<%s>', $type->type->accept($this));
    }

    public function visitClassString(ClassStringType $type): mixed
    {
        return 'class-string';
    }

    public function visitCallableString(CallableStringType $type): mixed
    {
        return 'callable-string';
    }

    public function visitInterfaceString(InterfaceStringType $type): mixed
    {
        return 'interface-string';
    }

    public function visitEnumString(EnumStringType $type): mixed
    {
        return 'enum-string';
    }

    public function visitTraitString(TraitStringType $type): mixed
    {
        return 'trait-string';
    }

    public function visitNonEmptyString(NonEmptyStringType $type): mixed
    {
        return 'non-empty-string';
    }

    public function visitString(StringType $type): mixed
    {
        return 'string';
    }

    public function visitNumeric(NumericType $type): mixed
    {
        return 'numeric';
    }

    public function visitArrayKey(ArrayKeyType $type): mixed
    {
        return 'array-key';
    }

    public function visitScalar(ScalarType $type): mixed
    {
        return 'scalar';
    }

    public function visitNonEmptyList(NonEmptyListType $type): mixed
    {
        if ($type->valueType instanceof MixedType) {
            return 'non-empty-list';
        }

        return $this->stringifyGenericType('non-empty-list', [$type->valueType]);
    }

    public function visitList(ListType $type): mixed
    {
        if ($type->valueType instanceof MixedType) {
            return 'list';
        }

        return $this->stringifyGenericType('list', [$type->valueType]);
    }

    public function visitShape(ShapeType $type): mixed
    {
        if (!$type->sealed && $type->elements === []) {
            return 'array';
        }

        $list = array_is_list($type->elements);

        return sprintf(
            '%s{%s%s}',
            $type->sealed && $list ? 'list' : 'array',
            implode(', ', array_map(
                fn (int|string $key, ShapeElement $element): string => $this->stringifyShapeElement($list, $key, $element),
                array_keys($type->elements),
                $type->elements,
            )),
            $type->sealed ? '' : ', ...',
        );
    }

    public function visitNonEmptyArray(NonEmptyArrayType $type): mixed
    {
        if ($type->keyType instanceof ArrayKeyType) {
            if ($type->valueType instanceof MixedType) {
                return 'non-empty-array';
            }

            return $this->stringifyGenericType('non-empty-array', [$type->valueType]);
        }

        return $this->stringifyGenericType('non-empty-array', [$type->keyType, $type->valueType]);
    }

    public function visitCallableArray(CallableArrayType $type): mixed
    {
        return 'callable-array';
    }

    public function visitArray(ArrayType $type): mixed
    {
        if ($type->keyType instanceof ArrayKeyType) {
            if ($type->valueType instanceof MixedType) {
                return 'array';
            }

            return $this->stringifyGenericType('array', [$type->valueType]);
        }

        return $this->stringifyGenericType('array', [$type->keyType, $type->valueType]);
    }

    public function visitIterable(IterableType $type): mixed
    {
        if ($type->keyType instanceof MixedType) {
            if ($type->valueType instanceof MixedType) {
                return 'iterable';
            }

            return $this->stringifyGenericType('iterable', [$type->valueType]);
        }

        return $this->stringifyGenericType('iterable', [$type->keyType, $type->valueType]);
    }

    public function visitNamedObject(NamedObjectType $type): mixed
    {
        if ($type->templateArguments === []) {
            return $type->class;
        }

        return $this->stringifyGenericType($type->class, $type->templateArguments);
    }

    public function visitStatic(StaticType $type): mixed
    {
        if ($type->templateArguments === []) {
            return 'static';
        }

        return $this->stringifyGenericType('static', $type->templateArguments);
    }

    public function visitObject(ObjectType $type): mixed
    {
        return 'object';
    }

    public function visitResource(ResourceType $type): mixed
    {
        return 'resource';
    }

    public function visitClosedResource(ClosedResourceType $type): mixed
    {
        return 'closed-resource';
    }

    public function visitClosure(ClosureType $type): mixed
    {
        return $this->stringifyCallable('Closure', $type->parameters, $type->returnType);
    }

    public function visitCallable(CallableType $type): mixed
    {
        return $this->stringifyCallable('callable', $type->parameters, $type->returnType);
    }

    public function visitConstant(ConstantType $type): mixed
    {
        return $type->constant;
    }

    public function visitClassConstant(ClassConstantType $type): mixed
    {
        return sprintf('%s::%s', $type->class, $type->constant);
    }

    public function visitKeyOf(KeyOfType $type): mixed
    {
        return $this->stringifyGenericType('key-of', [$type->type]);
    }

    public function visitValueOf(ValueOfType $type): mixed
    {
        return $this->stringifyGenericType('value-of', [$type->type]);
    }

    public function visitFunctionTemplate(FunctionTemplateType $type): mixed
    {
        return sprintf('%s:%s()', $type->name, $type->function);
    }

    public function visitClassTemplate(ClassTemplateType $type): mixed
    {
        return sprintf('%s:%s', $type->name, $type->class);
    }

    public function visitMethodTemplate(MethodTemplateType $type): mixed
    {
        return sprintf('%s:%s::%s()', $type->name, $type->class, $type->method);
    }

    public function visitIntersection(IntersectionType $type): mixed
    {
        /** @psalm-suppress MixedArgument */
        return implode('&', array_map(
            fn (Type $inner): string => $inner instanceof UnionType ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitUnion(UnionType $type): mixed
    {
        /** @psalm-suppress MixedArgument */
        return implode('|', array_map(
            fn (Type $inner): string => $inner instanceof IntersectionType ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitMixed(MixedType $type): mixed
    {
        return 'mixed';
    }

    private function stringifyShapeElement(bool $list, int|string $key, ShapeElement $element): string
    {
        if ($list && !$element->optional) {
            return $element->type->accept($this);
        }

        if (\is_string($key) && ($key === '' || preg_match('/\W/', $key))) {
            $key = $this->escapeStringLiteral($key);
        }

        return sprintf('%s%s: %s', $key, $element->optional ? '?' : '', $element->type->accept($this));
    }

    /**
     * @param non-empty-list<Type> $templateArguments
     */
    private function stringifyGenericType(string $name, array $templateArguments): string
    {
        return sprintf('%s<%s>', $name, implode(', ', array_map(
            fn (Type $type): string => $type->accept($this),
            $templateArguments,
        )));
    }

    /**
     * @param list<Parameter> $parameters
     */
    private function stringifyCallable(string $name, array $parameters, ?Type $returnType): string
    {
        if ($parameters === [] && $returnType === null) {
            return $name;
        }

        return sprintf(
            '%s(%s)%s',
            $name,
            implode(', ', array_map(
                fn (Parameter $parameter): string => $parameter->type->accept($this).match (true) {
                    $parameter->variadic => '...',
                    $parameter->hasDefault => '=',
                    default => '',
                },
                $parameters,
            )),
            $returnType === null ? '' : ': '.$returnType->accept($this),
        );
    }

    private function escapeStringLiteral(string $literal): string
    {
        return str_replace("\n", '\n', var_export($literal, return: true));
    }
}
