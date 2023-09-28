<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Psalm\Type\Atomic\TIntMask;

/**
 * @api
 */
final class types
{
    public const never = NeverType::type;
    public const void = VoidType::type;
    public const null = NullType::type;
    public const false = FalseType::type;
    public const true = TrueType::type;
    public const bool = BoolType::type;
    public const literalInt = LiteralIntType::type;
    public const int = IntType::type;
    public const float = FloatType::type;
    public const literalString = LiteralStringType::type;
    public const numericString = NumericStringType::type;
    public const classString = ClassStringType::type;
    public const callableString = CallableStringType::type;
    public const interfaceString = InterfaceStringType::type;
    public const enumString = EnumStringType::type;
    public const traitString = TraitStringType::type;
    public const nonEmptyString = NonEmptyStringType::type;
    public const truthyString = TruthyString::type;
    public const nonFalsyString = TruthyString::type;
    public const string = StringType::type;
    public const numeric = NumericType::type;
    public const scalar = ScalarType::type;
    public const callableArray = CallableArrayType::type;
    public const object = ObjectType::type;
    public const resource = ResourceType::type;
    public const closedResource = ClosedResourceType::type;
    public const arrayKey = ArrayKeyType::type;
    public const mixed = MixedType::type;

    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    /**
     * @psalm-pure
     * @return IntRangeType<negative-int>
     */
    public static function negativeInt(): IntRangeType
    {
        /** @var IntRangeType<negative-int> */
        return new IntRangeType(max: -1);
    }

    /**
     * @psalm-pure
     * @return IntRangeType<non-positive-int>
     */
    public static function nonPositiveInt(): IntRangeType
    {
        /** @var IntRangeType<non-positive-int> */
        return new IntRangeType(max: 0);
    }

    /**
     * @psalm-pure
     * @return IntRangeType<non-negative-int>
     */
    public static function nonNegativeInt(): IntRangeType
    {
        /** @var IntRangeType<non-negative-int> */
        return new IntRangeType(0);
    }

    /**
     * @psalm-pure
     * @return IntRangeType<positive-int>
     */
    public static function positiveInt(): IntRangeType
    {
        /** @var IntRangeType<positive-int> */
        return new IntRangeType(1);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @param int<0, max> $int
     * @param int<0, max> ...$ints
     */
    public static function intMask(int $int, int ...$ints): IntMaskType
    {
        return new IntMaskType([$int, ...$ints]);
    }

    /**
     * @psalm-pure
     * @template TIntMask of positive-int
     * @param Type<TIntMask> $type
     * @return IntMaskOfType<TIntMask>
     */
    public static function intMaskOf(Type $type): IntMaskOfType
    {
        return new IntMaskOfType($type);
    }

    /**
     * @psalm-pure
     */
    public static function int(?int $min = null, ?int $max = null): IntRangeType
    {
        return new IntRangeType($min, $max);
    }

    /**
     * @psalm-pure
     * @psalm-suppress NoValue, InvalidTemplateParam, TypeDoesNotContainType
     * @template TValue of int|float|string
     * @param TValue $value
     * @return ($value is int ? IntLiteralType<TValue> : ($value is float ? FloatLiteralType<TValue> : StringLiteralType<TValue>))
     */
    public static function literal(int|float|string $value): IntLiteralType|FloatLiteralType|StringLiteralType
    {
        if (\is_int($value)) {
            return new IntLiteralType($value);
        }

        if (\is_float($value)) {
            return new FloatLiteralType($value);
        }

        return new StringLiteralType($value);
    }

    /**
     * @psalm-pure
     * @template TValue of int
     * @param TValue $value
     * @return IntLiteralType<TValue>
     */
    public static function intLiteral(int $value): IntLiteralType
    {
        return new IntLiteralType($value);
    }

    /**
     * @psalm-pure
     * @template TValue of float
     * @param TValue $value
     * @return FloatLiteralType<TValue>
     */
    public static function floatLiteral(float $value): FloatLiteralType
    {
        return new FloatLiteralType($value);
    }

    /**
     * @psalm-pure
     * @template TValue of string
     * @param TValue $value
     * @return StringLiteralType<TValue>
     */
    public static function stringLiteral(string $value): StringLiteralType
    {
        return new StringLiteralType($value);
    }

    /**
     * @psalm-pure
     * @template TObject of object
     * @param Type<TObject> $type
     * @return NamedClassStringType<TObject>
     */
    public static function classString(Type $type): NamedClassStringType
    {
        return new NamedClassStringType($type);
    }

    /**
     * @psalm-pure
     * @template TValue
     * @param Type<TValue> $valueType
     * @return NonEmptyListType<TValue>
     */
    public static function nonEmptyList(Type $valueType = self::mixed): NonEmptyListType
    {
        if ($valueType === self::mixed) {
            return __nonEmptyList;
        }

        return new NonEmptyListType($valueType);
    }

    /**
     * @psalm-pure
     * @template TValue
     * @param Type<TValue> $valueType
     * @return ListType<TValue>
     */
    public static function list(Type $valueType = self::mixed): ListType
    {
        if ($valueType === self::mixed) {
            return __list;
        }

        return new ListType($valueType);
    }

    /**
     * @psalm-pure
     * @param array<Type|ShapeElement> $elements
     */
    public static function shape(array $elements = [], bool $sealed = true): ShapeType
    {
        return new ShapeType(
            array_map(
                static fn (Type|ShapeElement $element): ShapeElement => $element instanceof Type
                    ? new ShapeElement($element)
                    : $element,
                $elements,
            ),
            $sealed,
        );
    }

    /**
     * @psalm-pure
     * @param array<Type|ShapeElement> $elements
     */
    public static function unsealedShape(array $elements = []): ShapeType
    {
        return self::shape($elements, false);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return ShapeElement<TType>
     */
    public static function element(Type $type, bool $optional): ShapeElement
    {
        return new ShapeElement($type, $optional);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return ShapeElement<TType>
     */
    public static function optional(Type $type): ShapeElement
    {
        return new ShapeElement($type, true);
    }

    /**
     * @psalm-pure
     * @template TKey of array-key
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return NonEmptyArrayType<TKey, TValue>
     */
    public static function nonEmptyArray(Type $keyType = self::arrayKey, Type $valueType = self::mixed): NonEmptyArrayType
    {
        if ($keyType === self::arrayKey && $valueType === self::mixed) {
            return __nonEmptyArray;
        }

        return new NonEmptyArrayType($keyType, $valueType);
    }

    /**
     * @psalm-pure
     * @template TKey of array-key
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return ArrayType<TKey, TValue>
     */
    public static function array(Type $keyType = self::arrayKey, Type $valueType = self::mixed): ArrayType
    {
        if ($keyType === self::arrayKey && $valueType === self::mixed) {
            return __array;
        }

        return new ArrayType($keyType, $valueType);
    }

    /**
     * @psalm-pure
     * @template TKey
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return IterableType<TKey, TValue>
     */
    public static function iterable(Type $keyType = self::mixed, Type $valueType = self::mixed): IterableType
    {
        if ($keyType === self::mixed && $valueType === self::mixed) {
            return __iterable;
        }

        return new IterableType($keyType, $valueType);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TObject of object
     * @param class-string<TObject> $class
     * @return NamedObjectType<TObject>
     */
    public static function object(string $class, Type ...$templateArguments): NamedObjectType
    {
        return new NamedObjectType($class, $templateArguments);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TObject of object
     * @param class-string<TObject> $declaringClass
     * @return StaticType<TObject>
     */
    public static function static(string $declaringClass, Type ...$templateArguments): StaticType
    {
        return new StaticType($declaringClass, $templateArguments);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Parameter<TType>
     */
    public static function param(Type $type = self::mixed, bool $hasDefault = false, bool $variadic = false): Parameter
    {
        return new Parameter($type, $hasDefault, $variadic);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Parameter<TType>
     */
    public static function defaultParam(Type $type = self::mixed): Parameter
    {
        return new Parameter($type, true);
    }

    /**
     * @psalm-pure
     * @template TType
     * @param Type<TType> $type
     * @return Parameter<TType>
     */
    public static function variadicParam(Type $type = self::mixed): Parameter
    {
        return new Parameter($type, variadic: true);
    }

    /**
     * @psalm-pure
     * @template TReturn
     * @param list<Type|Parameter> $parameters
     * @param Type<TReturn> $returnType
     * @return ClosureType<TReturn>
     */
    public static function closure(array $parameters = [], ?Type $returnType = null): ClosureType
    {
        if ($parameters === [] && $returnType === null) {
            return __closure;
        }

        return new ClosureType(
            array_map(
                static fn (Type|Parameter $parameter): Parameter => $parameter instanceof Type
                    ? new Parameter($parameter)
                    : $parameter,
                $parameters,
            ),
            $returnType,
        );
    }

    /**
     * @psalm-pure
     * @template TReturn
     * @param list<Type|Parameter> $parameters
     * @param Type<TReturn> $returnType
     * @return CallableType<TReturn>
     */
    public static function callable(array $parameters = [], ?Type $returnType = null): CallableType
    {
        if ($parameters === [] && $returnType === null) {
            return __callable;
        }

        return new CallableType(
            array_map(
                static fn (Type|Parameter $parameter): Parameter => $parameter instanceof Type
                    ? new Parameter($parameter)
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
    public static function constant(string $constant): ConstantType
    {
        return new ConstantType($constant);
    }

    /**
     * @psalm-pure
     * @param class-string $class
     * @param non-empty-string $constant
     */
    public static function classConstant(string $class, string $constant): ClassConstantType
    {
        return new ClassConstantType($class, $constant);
    }

    /**
     * @psalm-pure
     */
    public static function keyOf(Type $type): KeyOfType
    {
        return new KeyOfType($type);
    }

    /**
     * @psalm-pure
     */
    public static function valueOf(Type $type): ValueOfType
    {
        return new ValueOfType($type);
    }

    /**
     * @psalm-pure
     * @param class-string $class
     * @param non-empty-string $name
     */
    public static function classTemplate(string $class, string $name): ClassTemplateType
    {
        return new ClassTemplateType($class, $name);
    }

    /**
     * @psalm-pure
     * @param class-string $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     */
    public static function methodTemplate(string $class, string $method, string $name): MethodTemplateType
    {
        return new MethodTemplateType($class, $method, $name);
    }

    /**
     * @psalm-pure
     * @param callable-string $function
     * @param non-empty-string $name
     */
    public static function functionTemplate(string $function, string $name): FunctionTemplateType
    {
        return new FunctionTemplateType($function, $name);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TType
     * @param Type<TType> $type
     * @return UnionType<?TType>
     */
    public static function nullable(Type $type): UnionType
    {
        return new UnionType([self::null, $type]);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function intersection(Type $type1, Type $type2, Type ...$moreTypes): IntersectionType
    {
        return new IntersectionType([$type1, $type2, ...$moreTypes]);
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     * @template TType
     * @param Type<TType> $type1
     * @param Type<TType> $type2
     * @param Type<TType> ...$moreTypes
     * @return UnionType<TType>
     */
    public static function union(Type $type1, Type $type2, Type ...$moreTypes): UnionType
    {
        return new UnionType([$type1, $type2, ...$moreTypes]);
    }
}

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __nonEmptyList = new NonEmptyListType();

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __list = new ListType();

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __nonEmptyArray = new NonEmptyArrayType();

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __array = new ArrayType();

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __iterable = new IterableType();

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __closure = new ClosureType();

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __callable = new CallableType();
