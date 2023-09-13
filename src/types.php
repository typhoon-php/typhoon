<?php

declare(strict_types=1);

namespace Typhoon;

/**
 * @api
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
     * @return Type\IntRangeType<negative-int>
     */
    public static function negativeInt(): Type\IntRangeType
    {
        /** @var Type\IntRangeType<negative-int> */
        return new Type\IntRangeType(max: -1);
    }

    /**
     * @psalm-pure
     * @return Type\IntRangeType<non-positive-int>
     */
    public static function nonPositiveInt(): Type\IntRangeType
    {
        /** @var Type\IntRangeType<non-positive-int> */
        return new Type\IntRangeType(max: 0);
    }

    /**
     * @psalm-pure
     * @return Type\IntRangeType<non-negative-int>
     */
    public static function nonNegativeInt(): Type\IntRangeType
    {
        /** @var Type\IntRangeType<non-negative-int> */
        return new Type\IntRangeType(0);
    }

    /**
     * @psalm-pure
     * @return Type\IntRangeType<positive-int>
     */
    public static function positiveInt(): Type\IntRangeType
    {
        /** @var Type\IntRangeType<positive-int> */
        return new Type\IntRangeType(1);
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
     * @psalm-suppress NoValue, InvalidTemplateParam, TypeDoesNotContainType
     * @template TValue of int|float|string
     * @param TValue $value
     * @return ($value is int ? Type\IntLiteralType<TValue> : ($value is float ? Type\FloatLiteralType<TValue> : Type\StringLiteralType<TValue>))
     */
    public static function literal(int|float|string $value): Type\IntLiteralType|Type\FloatLiteralType|Type\StringLiteralType
    {
        if (\is_int($value)) {
            return new Type\IntLiteralType($value);
        }

        if (\is_float($value)) {
            return new Type\FloatLiteralType($value);
        }

        return new Type\StringLiteralType($value);
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
        if ($valueType === self::mixed) {
            return __nonEmptyList;
        }

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
        if ($valueType === self::mixed) {
            return __list;
        }

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
                static fn (Type|Type\ShapeElement $element): Type\ShapeElement => $element instanceof Type
                    ? new Type\ShapeElement($element)
                    : $element,
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
    public static function element(Type $type, bool $optional): Type\ShapeElement
    {
        return new Type\ShapeElement($type, $optional);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Type\ShapeElement<TType>
     */
    public static function optional(Type $type): Type\ShapeElement
    {
        return new Type\ShapeElement($type, true);
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
        if ($keyType === self::arrayKey && $valueType === self::mixed) {
            return __nonEmptyArray;
        }

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
        if ($keyType === self::arrayKey && $valueType === self::mixed) {
            return __array;
        }

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
        if ($keyType === self::mixed && $valueType === self::mixed) {
            return __iterable;
        }

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
        return new Type\Parameter($type, $hasDefault, $variadic);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Type\Parameter<TType>
     */
    public static function defaultParam(Type $type = self::mixed): Type\Parameter
    {
        return new Type\Parameter($type, true);
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
        if ($parameters === [] && $returnType === null) {
            return __closure;
        }

        return new Type\ClosureType(
            array_map(
                static fn (Type|Type\Parameter $parameter): Type\Parameter => $parameter instanceof Type
                    ? new Type\Parameter($parameter)
                    : $parameter,
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
        if ($parameters === [] && $returnType === null) {
            return __callable;
        }

        return new Type\CallableType(
            array_map(
                static fn (Type|Type\Parameter $parameter): Type\Parameter => $parameter instanceof Type
                    ? new Type\Parameter($parameter)
                    : $parameter,
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
     * @param class-string $class
     * @param non-empty-string $name
     */
    public static function classTemplate(string $class, string $name): Type\ClassTemplateType
    {
        return new Type\ClassTemplateType($class, $name);
    }

    /**
     * @psalm-pure
     * @param class-string $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     */
    public static function methodTemplate(string $class, string $method, string $name): Type\MethodTemplateType
    {
        return new Type\MethodTemplateType($class, $method, $name);
    }

    /**
     * @psalm-pure
     * @param callable-string $function
     * @param non-empty-string $name
     */
    public static function functionTemplate(string $function, string $name): Type\FunctionTemplateType
    {
        return new Type\FunctionTemplateType($function, $name);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TType
     * @param Type<TType> $type
     * @return Type\UnionType<?TType>
     */
    public static function nullable(Type $type): Type\UnionType
    {
        return new Type\UnionType([self::null, $type]);
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

/**
 * @internal
 * @psalm-internal Typhoon
 */
const __nonEmptyList = new Type\NonEmptyListType();

/**
 * @internal
 * @psalm-internal Typhoon
 */
const __list = new Type\ListType();

/**
 * @internal
 * @psalm-internal Typhoon
 */
const __nonEmptyArray = new Type\NonEmptyArrayType();

/**
 * @internal
 * @psalm-internal Typhoon
 */
const __array = new Type\ArrayType();

/**
 * @internal
 * @psalm-internal Typhoon
 */
const __iterable = new Type\IterableType();

/**
 * @internal
 * @psalm-internal Typhoon
 */
const __closure = new Type\ClosureType();

/**
 * @internal
 * @psalm-internal Typhoon
 */
const __callable = new Type\CallableType();
