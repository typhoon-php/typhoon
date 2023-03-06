<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 */
final class types
{
    public const never = Type\NeverType::type;
    public const void = Type\VoidType::type;
    public const null = Type\NullType::type;
    public const false = Type\FalseType::type;
    public const true = Type\TrueType::type;
    public const bool = Type\BoolType::type;
    public const literalInt = Type\LiteralIntType::type;
    public const negativeInt = Type\NegativeIntType::type;
    public const nonPositiveInt = Type\NonPositiveIntType::type;
    public const nonNegativeInt = Type\NonNegativeIntType::type;
    public const positiveInt = Type\PositiveIntType::type;
    public const int = Type\IntType::type;
    public const float = Type\FloatType::type;
    public const literalString = Type\LiteralStringType::type;
    public const numericString = Type\NumericStringType::type;
    public const classString = Type\ClassStringType::type;
    public const callableString = Type\CallableStringType::type;
    public const interfaceString = Type\InterfaceStringType::type;
    public const enumString = Type\EnumStringType::type;
    public const traitString = Type\TraitStringType::type;
    public const nonEmptyString = Type\NonEmptyStringType::type;
    public const string = Type\StringType::type;
    public const numeric = Type\NumericType::type;
    public const scalar = Type\ScalarType::type;
    public const callableArray = Type\CallableArrayType::type;
    public const object = Type\ObjectType::type;
    public const resource = Type\ResourceType::type;
    public const closedResource = Type\ClosedResourceType::type;
    public const arrayKey = Type\ArrayKeyType::type;
    public const mixed = Type\MixedType::type;

    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     */
    public static function int(?int $min = null, ?int $max = null): Type\IntRangeType
    {
        return new Type\IntRangeType($min, $max);
    }

    /**
     * @psalm-pure
     * @template TValue of int
     * @param TValue $value
     * @return Type\IntLiteralType<TValue>
     */
    public static function intLiteral(int $value): Type\IntLiteralType
    {
        return new Type\IntLiteralType($value);
    }

    /**
     * @psalm-pure
     * @template TValue of float
     * @param TValue $value
     * @return Type\FloatLiteralType<TValue>
     */
    public static function floatLiteral(float $value): Type\FloatLiteralType
    {
        return new Type\FloatLiteralType($value);
    }

    /**
     * @psalm-pure
     * @template TValue of string
     * @param TValue $value
     * @return Type\StringLiteralType<TValue>
     */
    public static function stringLiteral(string $value): Type\StringLiteralType
    {
        return new Type\StringLiteralType($value);
    }

    /**
     * @psalm-pure
     * @template TObject of object
     * @param Type<TObject> $type
     * @return Type\NamedClassStringType<TObject>
     */
    public static function classString(Type $type): Type\NamedClassStringType
    {
        return new Type\NamedClassStringType($type);
    }

    /**
     * @psalm-pure
     * @template TValue
     * @param Type<TValue> $valueType
     * @return Type\NonEmptyListType<TValue>
     */
    public static function nonEmptyList(Type $valueType = self::mixed): Type\NonEmptyListType
    {
        return new Type\NonEmptyListType($valueType);
    }

    /**
     * @psalm-pure
     * @template TValue
     * @param Type<TValue> $valueType
     * @return Type\ListType<TValue>
     */
    public static function list(Type $valueType = self::mixed): Type\ListType
    {
        return new Type\ListType($valueType);
    }

    /**
     * @psalm-pure
     * @param array<Type|Type\ShapeElement> $elements
     */
    public static function shape(array $elements = [], bool $sealed = true): Type\ShapeType
    {
        return new Type\ShapeType(
            array_map(
                static fn (Type|Type\ShapeElement $element): Type\ShapeElement => $element instanceof Type ? new Type\ShapeElement($element) : $element,
                $elements,
            ),
            $sealed,
        );
    }

    /**
     * @psalm-pure
     * @param array<Type|Type\ShapeElement> $elements
     */
    public static function unsealedShape(array $elements = []): Type\ShapeType
    {
        return self::shape($elements, false);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Type\ShapeElement<TType>
     */
    public static function optional(Type $type): Type\ShapeElement
    {
        return new Type\ShapeElement($type, optional: true);
    }

    /**
     * @psalm-pure
     * @template TKey of array-key
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return Type\NonEmptyArrayType<TKey, TValue>
     */
    public static function nonEmptyArray(Type $keyType = self::arrayKey, Type $valueType = self::mixed): Type\NonEmptyArrayType
    {
        return new Type\NonEmptyArrayType($keyType, $valueType);
    }

    /**
     * @psalm-pure
     * @template TKey of array-key
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return Type\ArrayType<TKey, TValue>
     */
    public static function array(Type $keyType = self::arrayKey, Type $valueType = self::mixed): Type\ArrayType
    {
        return new Type\ArrayType($keyType, $valueType);
    }

    /**
     * @psalm-pure
     * @template TKey
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return Type\IterableType<TKey, TValue>
     */
    public static function iterable(Type $keyType = self::mixed, Type $valueType = self::mixed): Type\IterableType
    {
        return new Type\IterableType($keyType, $valueType);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TObject of object
     * @param class-string<TObject> $class
     * @return Type\NamedObjectType<TObject>
     */
    public static function object(string $class, Type ...$templateArguments): Type\NamedObjectType
    {
        return new Type\NamedObjectType($class, $templateArguments);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TObject of object
     * @param class-string<TObject> $declaringClass
     * @return Type\StaticType<TObject>
     */
    public static function static(string $declaringClass, Type ...$templateArguments): Type\StaticType
    {
        return new Type\StaticType($declaringClass, $templateArguments);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Type\Parameter<TType>
     */
    public static function param(Type $type = self::mixed, bool $hasDefault = false, bool $variadic = false): Type\Parameter
    {
        return new Type\Parameter($type, hasDefault: $hasDefault, variadic: $variadic);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Type\Parameter<TType>
     */
    public static function defaultParam(Type $type = self::mixed): Type\Parameter
    {
        return new Type\Parameter($type, hasDefault: true);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Type\Parameter<TType>
     */
    public static function variadicParam(Type $type = self::mixed): Type\Parameter
    {
        return new Type\Parameter($type, variadic: true);
    }

    /**
     * @psalm-pure
     * @template TReturn
     * @param list<Type|Type\Parameter> $parameters
     * @param Type<TReturn> $returnType
     * @return Type\ClosureType<TReturn>
     */
    public static function closure(array $parameters = [], ?Type $returnType = null): Type\ClosureType
    {
        return new Type\ClosureType(
            array_map(
                static fn (Type|Type\Parameter $parameter): Type\Parameter => $parameter instanceof Type ? new Type\Parameter($parameter) : $parameter,
                $parameters,
            ),
            $returnType,
        );
    }

    /**
     * @psalm-pure
     * @template TReturn
     * @param list<Type|Type\Parameter> $parameters
     * @param Type<TReturn> $returnType
     * @return Type\CallableType<TReturn>
     */
    public static function callable(array $parameters = [], ?Type $returnType = null): Type\CallableType
    {
        return new Type\CallableType(
            array_map(
                static fn (Type|Type\Parameter $parameter): Type\Parameter => $parameter instanceof Type ? new Type\Parameter($parameter) : $parameter,
                $parameters,
            ),
            $returnType,
        );
    }

    /**
     * @psalm-pure
     * @param non-empty-string $constant
     */
    public static function constant(string $constant): Type\ConstantType
    {
        return new Type\ConstantType($constant);
    }

    /**
     * @psalm-pure
     * @param class-string $class
     * @param non-empty-string $constant
     */
    public static function classConstant(string $class, string $constant): Type\ClassConstantType
    {
        return new Type\ClassConstantType($class, $constant);
    }

    /**
     * @psalm-pure
     */
    public static function keyOf(Type $type): Type\KeyOfType
    {
        return new Type\KeyOfType($type);
    }

    /**
     * @psalm-pure
     */
    public static function valueOf(Type $type): Type\ValueOfType
    {
        return new Type\ValueOfType($type);
    }

    /**
     * @psalm-pure
     * @param non-empty-string $name
     * @param class-string $class
     */
    public static function classTemplate(string $name, string $class): Type\ClassTemplateType
    {
        return new Type\ClassTemplateType($name, $class);
    }

    /**
     * @psalm-pure
     * @param non-empty-string $name
     * @param class-string $class
     * @param non-empty-string $method
     */
    public static function methodTemplate(string $name, string $class, string $method): Type\MethodTemplateType
    {
        return new Type\MethodTemplateType($name, $class, $method);
    }

    /**
     * @psalm-pure
     * @param non-empty-string $name
     * @param callable-string $function
     */
    public static function functionTemplate(string $name, string $function): Type\FunctionTemplateType
    {
        return new Type\FunctionTemplateType($name, $function);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TType
     * @param Type<TType> $type
     * @return Type\NullableType<TType>
     */
    public static function nullable(Type $type): Type\NullableType
    {
        return new Type\NullableType($type);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function intersection(Type $type1, Type $type2, Type ...$moreTypes): Type\IntersectionType
    {
        return new Type\IntersectionType([$type1, $type2, ...$moreTypes]);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TType
     * @param Type<TType> $type1
     * @param Type<TType> $type2
     * @param Type<TType> ...$moreTypes
     * @return Type\UnionType<TType>
     */
    public static function union(Type $type1, Type $type2, Type ...$moreTypes): Type\UnionType
    {
        return new Type\UnionType([$type1, $type2, ...$moreTypes]);
    }
}
