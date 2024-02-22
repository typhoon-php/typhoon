<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
final class types
{
    public const arrayKey = __arrayKey;
    public const bool = BoolType::type;
    public const classString = ClassStringType::type;
    public const false = __false;
    public const float = FloatType::type;
    public const int = IntType::type;
    public const mixed = MixedType::type;
    public const negativeInt = __negativeInt;
    public const never = NeverType::type;
    public const nonEmptyString = __nonEmptyString;
    public const nonFalsyString = TruthyStringType::type;
    public const nonNegativeInt = __nonNegativeInt;
    public const nonPositiveInt = __nonPositiveInt;
    public const null = NullType::type;
    public const numeric = __numeric;
    public const numericString = NumericStringType::type;
    public const object = ObjectType::type;
    public const positiveInt = __positiveInt;
    public const resource = ResourceType::type;
    public const scalar = __scalar;
    public const string = StringType::type;
    public const true = __true;
    public const truthyString = TruthyStringType::type;
    public const void = VoidType::type;

    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public static function alias(string $class, string $name): AliasType
    {
        return new AliasType($class, $name);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return AnyLiteralType<TType>
     */
    public static function anyLiteral(Type $type): AnyLiteralType
    {
        return new AnyLiteralType($type);
    }

    /**
     * @param non-empty-string $name
     */
    public static function arg(string $name): Argument
    {
        return new Argument($name);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return ArrayType<TKey, TValue>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public static function array(Type $keyType = self::arrayKey, Type $valueType = self::mixed): ArrayType
    {
        if ($keyType === self::arrayKey && $valueType === self::mixed) {
            return __array;
        }

        return new ArrayType($keyType, $valueType);
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
     * @param non-empty-string $name
     */
    public static function atClass(string $name): AtClass
    {
        return new AtClass($name);
    }

    /**
     * @param non-empty-string $name
     */
    public static function atFunction(string $name): AtFunction
    {
        return new AtFunction($name);
    }

    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public static function atMethod(string $class, string $name): AtMethod
    {
        return new AtMethod($class, $name);
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
     * @param non-empty-string $class
     * @param non-empty-string $constant
     */
    public static function classConstant(string $class, string $constant): ClassConstantType
    {
        return new ClassConstantType($class, $constant);
    }

    /**
     * @template TObject of object
     * @param Type<TObject> $type
     * @return NamedClassStringType<TObject>
     */
    public static function classString(Type $type): NamedClassStringType
    {
        return new NamedClassStringType($type);
    }

    /**
     * @template TClass of non-empty-string
     * @param TClass $class
     * @return ClassStringLiteralType<TClass>
     */
    public static function classStringLiteral(string $class): ClassStringLiteralType
    {
        return new ClassStringLiteralType($class);
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

    public static function conditional(Argument|TemplateType $subject, Type $if, Type $then, Type $else): ConditionalType
    {
        return new ConditionalType($subject, $if, $then, $else);
    }

    /**
     * @param non-empty-string $constant
     */
    public static function constant(string $constant): ConstantType
    {
        return new ConstantType($constant);
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

    public static function keyOf(Type $type): KeyOfType
    {
        return new KeyOfType($type);
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
     * @template TValue of bool|int|float|string
     * @param TValue $value
     * @return LiteralType<TValue>
     */
    public static function literal(bool|int|float|string $value): LiteralType
    {
        return new LiteralType($value);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return Type<non-empty-array<TKey, TValue>>
     * @psalm-suppress MoreSpecificReturnType, LessSpecificReturnStatement
     */
    public static function nonEmptyArray(Type $keyType = self::arrayKey, Type $valueType = self::mixed): Type
    {
        return new NonEmptyType(self::array($keyType, $valueType));
    }

    /**
     * @template TValue
     * @param Type<TValue> $valueType
     * @return Type<non-empty-list<TValue>>
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public static function nonEmptyList(Type $valueType = self::mixed): Type
    {
        return new NonEmptyType(self::list($valueType));
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
     * @template TObject of object
     * @param class-string<TObject>|non-empty-string $class
     * @return ($class is class-string ? NamedObjectType<TObject> : NamedObjectType<object>)
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public static function object(string $class, Type ...$templateArguments): NamedObjectType
    {
        return new NamedObjectType($class, $templateArguments);
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

    public static function offset(Type $subject, Type $offset): OffsetType
    {
        return new OffsetType($subject, $offset);
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

    public static function prop(Type $type, bool $optional = false): Property
    {
        return new Property($type, $optional);
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

    public static function valueOf(Type $type): ValueOfType
    {
        return new ValueOfType($type);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return VarianceAwareType<TType>
     */
    public static function varianceAware(Type $type, Variance $variance): VarianceAwareType
    {
        return new VarianceAwareType($type, $variance);
    }
}

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __true = new LiteralType(true);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __false = new LiteralType(false);

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
const __nonEmptyString = new NonEmptyType(StringType::type);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __arrayKey = new UnionType([IntType::type, StringType::type]);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __scalar = new UnionType([BoolType::type, IntType::type, FloatType::type, StringType::type]);

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
const __numeric = new UnionType([IntType::type, FloatType::type, NumericStringType::type]);

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
