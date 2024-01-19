<?php

declare(strict_types=1);

namespace Typhoon\TypeStringifier;

use Typhoon\Type;
use Typhoon\Type\ClassStringLiteralType;
use Typhoon\Type\IntMaskOfType;
use Typhoon\Type\IntMaskType;
use Typhoon\Type\ObjectShapeType;
use Typhoon\Type\TypeVisitor;

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
    public static function stringify(Type\Type $type): string
    {
        return $type->accept(new self());
    }

    public function visitNever(Type\NeverType $type): mixed
    {
        return 'never';
    }

    public function visitVoid(Type\VoidType $type): mixed
    {
        return 'void';
    }

    public function visitNull(Type\NullType $type): mixed
    {
        return 'null';
    }

    public function visitFalse(Type\FalseType $type): mixed
    {
        return 'false';
    }

    public function visitTrue(Type\TrueType $type): mixed
    {
        return 'true';
    }

    public function visitBool(Type\BoolType $type): mixed
    {
        return 'bool';
    }

    public function visitIntLiteral(Type\IntLiteralType $type): mixed
    {
        return (string) $type->value;
    }

    public function visitLiteralInt(Type\LiteralIntType $type): mixed
    {
        return 'literal-int';
    }

    public function visitIntRange(Type\IntRangeType $type): mixed
    {
        if ($type->min === null && $type->max === null) {
            return 'int';
        }

        return sprintf('int<%s, %s>', $type->min ?? 'min', $type->max ?? 'max');
    }

    public function visitIntMask(IntMaskType $type): mixed
    {
        return sprintf('int-mask<%s>', implode(', ', $type->ints));
    }

    public function visitIntMaskOf(IntMaskOfType $type): mixed
    {
        return sprintf('int-mask-of<%s>', $type->type->accept($this));
    }

    public function visitInt(Type\IntType $type): mixed
    {
        return 'int';
    }

    public function visitFloatLiteral(Type\FloatLiteralType $type): mixed
    {
        return (string) $type->value;
    }

    public function visitFloat(Type\FloatType $type): mixed
    {
        return 'float';
    }

    public function visitStringLiteral(Type\StringLiteralType $type): mixed
    {
        return $this->escapeStringLiteral($type->value);
    }

    public function visitLiteralString(Type\LiteralStringType $type): mixed
    {
        return 'literal-string';
    }

    public function visitNumericString(Type\NumericStringType $type): mixed
    {
        return 'numeric-string';
    }

    public function visitClassStringLiteral(ClassStringLiteralType $type): mixed
    {
        return $type->class . '::class';
    }

    public function visitNamedClassString(Type\NamedClassStringType $type): mixed
    {
        return sprintf('class-string<%s>', $type->type->accept($this));
    }

    public function visitClassString(Type\ClassStringType $type): mixed
    {
        return 'class-string';
    }

    public function visitCallableString(Type\CallableStringType $type): mixed
    {
        return 'callable-string';
    }

    public function visitInterfaceString(Type\InterfaceStringType $type): mixed
    {
        return 'interface-string';
    }

    public function visitEnumString(Type\EnumStringType $type): mixed
    {
        return 'enum-string';
    }

    public function visitTraitString(Type\TraitStringType $type): mixed
    {
        return 'trait-string';
    }

    public function visitNonEmptyString(Type\NonEmptyStringType $type): mixed
    {
        return 'non-empty-string';
    }

    public function visitTruthyString(Type\TruthyString $type): mixed
    {
        return 'truthy-string';
    }

    public function visitString(Type\StringType $type): mixed
    {
        return 'string';
    }

    public function visitNumeric(Type\NumericType $type): mixed
    {
        return 'numeric';
    }

    public function visitArrayKey(Type\ArrayKeyType $type): mixed
    {
        return 'array-key';
    }

    public function visitScalar(Type\ScalarType $type): mixed
    {
        return 'scalar';
    }

    public function visitNonEmptyList(Type\NonEmptyListType $type): mixed
    {
        if ($type->valueType instanceof Type\MixedType) {
            return 'non-empty-list';
        }

        return $this->stringifyGenericType('non-empty-list', [$type->valueType]);
    }

    public function visitList(Type\ListType $type): mixed
    {
        if ($type->valueType instanceof Type\MixedType) {
            return 'list';
        }

        return $this->stringifyGenericType('list', [$type->valueType]);
    }

    public function visitArrayShape(Type\ArrayShapeType $type): mixed
    {
        if ($type->elements === []) {
            return $type->sealed ? 'list{}' : 'array';
        }

        if (array_is_list($type->elements)) {
            return sprintf(
                '%s{%s%s}',
                $type->sealed ? 'list' : 'array',
                implode(', ', array_map(
                    fn(int $key, Type\ArrayElement $element) => ($element->optional ? $key . '?: ' : '') . $element->type->accept($this),
                    array_keys($type->elements),
                    $type->elements,
                )),
                $type->sealed ? '' : ', ...',
            );
        }

        return sprintf(
            'array{%s%s}',
            implode(', ', array_map(
                function (int|string $key, Type\ArrayElement $element): string {
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

    public function visitNonEmptyArray(Type\NonEmptyArrayType $type): mixed
    {
        if ($type->keyType instanceof Type\ArrayKeyType) {
            if ($type->valueType instanceof Type\MixedType) {
                return 'non-empty-array';
            }

            return $this->stringifyGenericType('non-empty-array', [$type->valueType]);
        }

        return $this->stringifyGenericType('non-empty-array', [$type->keyType, $type->valueType]);
    }

    public function visitCallableArray(Type\CallableArrayType $type): mixed
    {
        return 'callable-array';
    }

    public function visitArray(Type\ArrayType $type): mixed
    {
        if ($type->keyType instanceof Type\ArrayKeyType) {
            if ($type->valueType instanceof Type\MixedType) {
                return 'array';
            }

            return $this->stringifyGenericType('array', [$type->valueType]);
        }

        return $this->stringifyGenericType('array', [$type->keyType, $type->valueType]);
    }

    public function visitIterable(Type\IterableType $type): mixed
    {
        if ($type->keyType instanceof Type\MixedType) {
            if ($type->valueType instanceof Type\MixedType) {
                return 'iterable';
            }

            return $this->stringifyGenericType('iterable', [$type->valueType]);
        }

        return $this->stringifyGenericType('iterable', [$type->keyType, $type->valueType]);
    }

    public function visitNamedObject(Type\NamedObjectType $type): mixed
    {
        if ($type->templateArguments === []) {
            return $type->class;
        }

        return $this->stringifyGenericType($type->class, $type->templateArguments);
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        if ($type->templateArguments === []) {
            return 'static';
        }

        return $this->stringifyGenericType('static', $type->templateArguments);
    }

    public function visitObjectShape(ObjectShapeType $type): mixed
    {
        return sprintf('object{%s}', implode(', ', array_map(
            function (string $name, Type\Property $property): string {
                if ($name === '' || preg_match('/\W/', $name)) {
                    $name = $this->escapeStringLiteral($name);
                }

                return sprintf('%s%s: %s', $name, $property->optional ? '?' : '', $property->type->accept($this));
            },
            array_keys($type->properties),
            $type->properties,
        )));
    }

    public function visitObject(Type\ObjectType $type): mixed
    {
        return 'object';
    }

    public function visitResource(Type\ResourceType $type): mixed
    {
        return 'resource';
    }

    public function visitClosedResource(Type\ClosedResourceType $type): mixed
    {
        return 'closed-resource';
    }

    public function visitClosure(Type\ClosureType $type): mixed
    {
        return $this->stringifyCallable('Closure', $type->parameters, $type->returnType);
    }

    public function visitCallable(Type\CallableType $type): mixed
    {
        return $this->stringifyCallable('callable', $type->parameters, $type->returnType);
    }

    public function visitConstant(Type\ConstantType $type): mixed
    {
        return $type->constant;
    }

    public function visitClassConstant(Type\ClassConstantType $type): mixed
    {
        return sprintf('%s::%s', $type->class, $type->constant);
    }

    public function visitKeyOf(Type\KeyOfType $type): mixed
    {
        return $this->stringifyGenericType('key-of', [$type->type]);
    }

    public function visitValueOf(Type\ValueOfType $type): mixed
    {
        return $this->stringifyGenericType('value-of', [$type->type]);
    }

    public function visitTemplate(Type\TemplateType $type): mixed
    {
        return $type->name;
    }

    public function visitConditional(Type\ConditionalType $type): mixed
    {
        return sprintf(
            '(%s%s is %s ? %s : %s)',
            $type->subject instanceof Type\Argument ? '$' : '',
            $type->subject->name,
            $type->is->accept($this),
            $type->if->accept($this),
            $type->else->accept($this),
        );
    }

    public function visitIntersection(Type\IntersectionType $type): mixed
    {
        return implode('&', array_map(
            fn(Type\Type $inner): string => $inner instanceof Type\UnionType ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitUnion(Type\UnionType $type): mixed
    {
        return implode('|', array_map(
            fn(Type\Type $inner): string => $inner instanceof Type\IntersectionType ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitMixed(Type\MixedType $type): mixed
    {
        return 'mixed';
    }

    /**
     * @param non-empty-list<Type\Type> $templateArguments
     * @return non-empty-string
     */
    private function stringifyGenericType(string $name, array $templateArguments): string
    {
        return sprintf('%s<%s>', $name, implode(', ', array_map(
            fn(Type\Type $type): string => $type->accept($this),
            $templateArguments,
        )));
    }

    /**
     * @param non-empty-string $name
     * @param list<Type\Parameter> $parameters
     * @return non-empty-string
     */
    private function stringifyCallable(string $name, array $parameters, ?Type\Type $returnType): string
    {
        if ($parameters === [] && $returnType === null) {
            return $name;
        }

        return sprintf(
            '%s(%s)%s',
            $name,
            implode(', ', array_map(
                fn(Type\Parameter $parameter): string => $parameter->type->accept($this) . match (true) {
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
