<?php

declare(strict_types=1);

namespace Typhoon\Type;

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
    public const literalInt = AnyLiteralIntType::type;
    public const int = IntType::type;
    public const positiveInt = __positiveInt;
    public const negativeInt = __negativeInt;
    public const nonPositiveInt = __nonPositiveInt;
    public const nonNegativeInt = __nonNegativeInt;
    public const float = FloatType::type;
    public const literalString = AnyLiteralStringType::type;
    public const numericString = NumericStringType::type;
    public const classString = ClassStringType::type;
    public const interfaceString = InterfaceStringType::type;
    public const enumString = EnumStringType::type;
    public const traitString = TraitStringType::type;
    public const nonEmptyString = NonEmptyStringType::type;
    public const truthyString = TruthyStringType::type;
    public const nonFalsyString = TruthyStringType::type;
    public const string = StringType::type;
    public const numeric = NumericType::type;
    public const scalar = __scalar;
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
     * @no-named-arguments
     * @param non-negative-int $int
     * @param non-negative-int ...$ints
     */
    public static function intMask(int $int, int ...$ints): IntMaskType
    {
        return new IntMaskType([$int, ...$ints]);
    }

    /**
     * @template TIntMask of positive-int
     * @param Type<TIntMask> $type
     * @return IntMaskOfType<TIntMask>
     */
    public static function intMaskOf(Type $type): IntMaskOfType
    {
        return new IntMaskOfType($type);
    }

    public static function intRange(?int $min = null, ?int $max = null): IntType|IntRangeType
    {
        if ($min === null && $max === null) {
            return IntType::type;
        }

        return new IntRangeType($min, $max);
    }

    /**
     * @template TValue of int
     * @param TValue $value
     * @return IntLiteralType<TValue>
     */
    public static function int(int $value): IntLiteralType
    {
        return new IntLiteralType($value);
    }

    /**
     * @template TValue of float
     * @param TValue $value
     * @return FloatLiteralType<TValue>
     */
    public static function float(float $value): FloatLiteralType
    {
        return new FloatLiteralType($value);
    }

    /**
     * @template TValue of string
     * @param TValue $value
     * @return StringLiteralType<TValue>
     */
    public static function string(string $value): StringLiteralType
    {
        return new StringLiteralType($value);
    }

    /**
     * @template TClass of class-string
     * @template TObject of object
     * @param TClass|Type<TObject> $classOrType
     * @return ($classOrType is TClass ? ClassStringLiteralType<TClass> : NamedClassStringType<TObject>)
     * @psalm-suppress TypeDoesNotContainType, NoValue
     */
    public static function classString(string|Type $classOrType): ClassStringLiteralType|NamedClassStringType
    {
        if (\is_string($classOrType)) {
            return new ClassStringLiteralType($classOrType);
        }

        return new NamedClassStringType($classOrType);
    }

    /**
     * @template TValue
     * @param Type<TValue> $valueType
     * @return NonEmptyListType<TValue>
     */
    public static function nonEmptyList(Type $valueType = self::mixed): NonEmptyListType
    {
        return new NonEmptyListType($valueType);
    }

    /**
     * @template TValue
     * @param Type<TValue> $valueType
     * @return ListType<TValue>
     */
    public static function list(Type $valueType = self::mixed): ListType
    {
        return new ListType($valueType);
    }

    /**
     * @param array<Type|ArrayElement> $elements
     */
    public static function arrayShape(array $elements = [], bool $sealed = true): ArrayShapeType
    {
        return new ArrayShapeType(
            array_map(
                static fn(Type|ArrayElement $element): ArrayElement => $element instanceof Type
                    ? new ArrayElement($element)
                    : $element,
                $elements,
            ),
            $sealed,
        );
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return ArrayElement<TType>
     */
    public static function arrayElement(Type $type, bool $optional = false): ArrayElement
    {
        return new ArrayElement($type, $optional);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return NonEmptyArrayType<TKey, TValue>
     */
    public static function nonEmptyArray(Type $keyType = self::arrayKey, Type $valueType = self::mixed): NonEmptyArrayType
    {
        return new NonEmptyArrayType($keyType, $valueType);
    }

    /**
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
     * @param array<string, Type|Property> $properties
     */
    public static function objectShape(array $properties = []): ObjectShapeType
    {
        return new ObjectShapeType(
            array_map(
                static fn(Type|Property $property): Property => $property instanceof Type ? new Property($property) : $property,
                $properties,
            ),
        );
    }

    public static function prop(Type $type, bool $optional = false): Property
    {
        return new Property($type, $optional);
    }

    /**
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
     * @no-named-arguments
     * @template TObject of object
     * @param class-string<TObject> $declaredAtClass
     * @return StaticType<TObject>
     */
    public static function static(string $declaredAtClass, Type ...$templateArguments): StaticType
    {
        return new StaticType($declaredAtClass, $templateArguments);
    }

    /**
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
                static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type
                    ? new Parameter($parameter)
                    : $parameter,
                $parameters,
            ),
            $returnType,
        );
    }

    /**
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
                static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type
                    ? new Parameter($parameter)
                    : $parameter,
                $parameters,
            ),
            $returnType,
        );
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @param ?non-empty-string $name
     * @return Parameter<TType>
     */
    public static function param(Type $type = self::mixed, bool $hasDefault = false, bool $variadic = false, bool $byReference = false, ?string $name = null): Parameter
    {
        return new Parameter($type, $hasDefault, $variadic, $byReference, $name);
    }

    /**
     * @param non-empty-string $constant
     */
    public static function constant(string $constant): ConstantType
    {
        return new ConstantType($constant);
    }

    /**
     * @param class-string $class
     * @param non-empty-string $constant
     */
    public static function classConstant(string $class, string $constant): ClassConstantType
    {
        return new ClassConstantType($class, $constant);
    }

    public static function keyOf(Type $type): KeyOfType
    {
        return new KeyOfType($type);
    }

    public static function valueOf(Type $type): ValueOfType
    {
        return new ValueOfType($type);
    }

    public static function offset(Type $subject, Type $offset): OffsetType
    {
        return new OffsetType($subject, $offset);
    }

    /**
     * @template TType
     * @param non-empty-string $name
     * @param Type<TType> $constraint
     * @return TemplateType<TType>
     */
    public static function template(string $name, AtMethod|AtClass|AtFunction $declaredAt, Type $constraint = self::mixed): TemplateType
    {
        return new TemplateType($name, $declaredAt, $constraint);
    }

    /**
     * @param callable-string $name
     */
    public static function atFunction(string $name): AtFunction
    {
        return new AtFunction($name);
    }

    /**
     * @param class-string $name
     */
    public static function atClass(string $name): AtClass
    {
        return new AtClass($name);
    }

    /**
     * @param class-string $class
     * @param non-empty-string $name
     */
    public static function atMethod(string $class, string $name): AtMethod
    {
        return new AtMethod($class, $name);
    }

    public static function conditional(Argument|TemplateType $subject, Type $if, Type $then, Type $else): ConditionalType
    {
        return new ConditionalType($subject, $if, $then, $else);
    }

    /**
     * @param non-empty-string $name
     */
    public static function arg(string $name): Argument
    {
        return new Argument($name);
    }

    /**
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
     * @no-named-arguments
     */
    public static function intersection(Type $type1, Type $type2, Type ...$moreTypes): IntersectionType
    {
        return new IntersectionType([$type1, $type2, ...$moreTypes]);
    }

    /**
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
const __positiveInt = new IntRangeType(1);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __negativeInt = new IntRangeType(max: -1);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __nonPositiveInt = new IntRangeType(max: 0);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __nonNegativeInt = new IntRangeType(0);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __scalar = new UnionType([BoolType::type, IntType::type, FloatType::type, StringType::type]);

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
