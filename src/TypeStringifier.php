<?php

declare(strict_types=1);

namespace Typhoon;

use Typhoon\Type\ArrayKeyType;
use Typhoon\Type\ArrayType;
use Typhoon\Type\BoolType;
use Typhoon\Type\CallableArrayType;
use Typhoon\Type\CallableStringType;
use Typhoon\Type\CallableType;
use Typhoon\Type\ClassConstantType;
use Typhoon\Type\ClassStringType;
use Typhoon\Type\ClassTemplateType;
use Typhoon\Type\ClosedResourceType;
use Typhoon\Type\ClosureType;
use Typhoon\Type\ConstantType;
use Typhoon\Type\EnumStringType;
use Typhoon\Type\FalseType;
use Typhoon\Type\FloatLiteralType;
use Typhoon\Type\FloatType;
use Typhoon\Type\FunctionTemplateType;
use Typhoon\Type\InterfaceStringType;
use Typhoon\Type\IntersectionType;
use Typhoon\Type\IntLiteralType;
use Typhoon\Type\IntRangeType;
use Typhoon\Type\IntType;
use Typhoon\Type\IterableType;
use Typhoon\Type\KeyOfType;
use Typhoon\Type\ListType;
use Typhoon\Type\LiteralIntType;
use Typhoon\Type\LiteralStringType;
use Typhoon\Type\MethodTemplateType;
use Typhoon\Type\MixedType;
use Typhoon\Type\NamedClassStringType;
use Typhoon\Type\NamedObjectType;
use Typhoon\Type\NeverType;
use Typhoon\Type\NonEmptyArrayType;
use Typhoon\Type\NonEmptyListType;
use Typhoon\Type\NonEmptyStringType;
use Typhoon\Type\NullType;
use Typhoon\Type\NumericStringType;
use Typhoon\Type\NumericType;
use Typhoon\Type\ObjectType;
use Typhoon\Type\Parameter;
use Typhoon\Type\ResourceType;
use Typhoon\Type\ScalarType;
use Typhoon\Type\ShapeElement;
use Typhoon\Type\ShapeType;
use Typhoon\Type\StaticType;
use Typhoon\Type\StringLiteralType;
use Typhoon\Type\StringType;
use Typhoon\Type\TraitStringType;
use Typhoon\Type\TrueType;
use Typhoon\Type\UnionType;
use Typhoon\Type\ValueOfType;
use Typhoon\Type\VoidType;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements TypeVisitor<non-empty-string>
 * @psalm-suppress ImpureFunctionCall
 */
final class TypeStringifier implements TypeVisitor
{
    private function __construct() {}

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
        if ($type->elements === []) {
            return $type->sealed ? 'list{}' : 'array';
        }

        if (array_is_list($type->elements)) {
            return sprintf(
                '%s{%s%s}',
                $type->sealed ? 'list' : 'array',
                implode(', ', array_map(
                    fn (int $key, ShapeElement $element) => ($element->optional ? $key . '?: ' : '') . $element->type->accept($this),
                    array_keys($type->elements),
                    $type->elements,
                )),
                $type->sealed ? '' : ', ...',
            );
        }

        return sprintf(
            'array{%s%s}',
            implode(', ', array_map(
                function (int|string $key, ShapeElement $element): string {
                    if (\is_string($key) && ($key === '' || preg_match('/\W/', $key))) {
                        $key = $this->escapeStringLiteral($key);
                    }

                    return sprintf('%s%s: %s', $key, $element->optional ? '?' : '', $element->type->accept($this));
                },
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
        return implode('&', array_map(
            fn (Type $inner): string => $inner instanceof UnionType ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitUnion(UnionType $type): mixed
    {
        return implode('|', array_map(
            fn (Type $inner): string => $inner instanceof IntersectionType ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitMixed(MixedType $type): mixed
    {
        return 'mixed';
    }

    /**
     * @param non-empty-list<Type> $templateArguments
     * @return non-empty-string
     */
    private function stringifyGenericType(string $name, array $templateArguments): string
    {
        return sprintf('%s<%s>', $name, implode(', ', array_map(
            fn (Type $type): string => $type->accept($this),
            $templateArguments,
        )));
    }

    /**
     * @param non-empty-string $name
     * @param list<Parameter> $parameters
     * @return non-empty-string
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
                fn (Parameter $parameter): string => $parameter->type->accept($this) . match (true) {
                    $parameter->variadic => '...',
                    $parameter->hasDefault => '=',
                    default => '',
                },
                $parameters,
            )),
            $returnType === null ? '' : ': ' . $returnType->accept($this),
        );
    }

    /**
     * @return non-empty-string
     */
    private function escapeStringLiteral(string $literal): string
    {
        /** @var non-empty-string */
        return str_replace("\n", '\n', var_export($literal, return: true));
    }
}
