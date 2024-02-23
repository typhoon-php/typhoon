<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @implements Type<mixed>
 */
enum types implements Type
{
    case array;
    case arrayKey;
    case bool;
    case callable;
    case classString;
    case closure;
    case false;
    case float;
    case int;
    case iterable;
    case mixed;
    case negativeInt;
    case never;
    case nonNegativeInt;
    case nonPositiveInt;
    case null;
    case numericString;
    case object;
    case positiveInt;
    case resource;
    case scalar;
    case string;
    case true;
    case truthyString;
    case void;

    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public static function alias(string $class, string $name): Type
    {
        return new AliasType($class, $name);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<TType>
     */
    public static function anyLiteral(Type $type): Type
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
     * @return Type<array<TKey, TValue>>
     */
    public static function array(Type $keyType = self::arrayKey, Type $valueType = self::mixed): Type
    {
        if ($keyType === self::arrayKey && $valueType === self::mixed) {
            return self::array;
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
     * @return Type<array<mixed>>
     */
    public static function arrayShape(array $elements = [], bool $sealed = true): Type
    {
        return new ArrayShapeType(
            array_map(
                static fn(Type|ArrayElement $element): ArrayElement => $element instanceof Type ? new ArrayElement($element) : $element,
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
     * @return Type<callable>
     */
    public static function callable(array $parameters = [], ?Type $returnType = null): Type
    {
        if ($parameters === [] && $returnType === null) {
            return self::callable;
        }

        return new CallableType(
            array_map(
                static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type ? new Parameter($parameter) : $parameter,
                $parameters,
            ),
            $returnType,
        );
    }

    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public static function classConstant(string $class, string $name): Type
    {
        return new ClassConstantType($class, $name);
    }

    /**
     * @template TObject of object
     * @param Type<TObject> $type
     * @return Type<class-string<TObject>>
     */
    public static function classString(Type $type): Type
    {
        return new NamedClassStringType($type);
    }

    /**
     * @template TClass of non-empty-string
     * @param TClass $class
     * @return Type<TClass>
     */
    public static function classStringLiteral(string $class): Type
    {
        return new ClassStringLiteralType($class);
    }

    /**
     * @template TReturn
     * @param list<Type|Parameter> $parameters
     * @param Type<TReturn> $returnType
     * @return Type<\Closure(): TReturn>
     */
    public static function closure(array $parameters = [], ?Type $returnType = null): Type
    {
        if ($parameters === [] && $returnType === null) {
            return self::closure;
        }

        return new ClosureType(
            array_map(
                static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type ? new Parameter($parameter) : $parameter,
                $parameters,
            ),
            $returnType,
        );
    }

    public static function conditional(Argument|Type $subject, Type $if, Type $then, Type $else): Type
    {
        return new ConditionalType($subject, $if, $then, $else);
    }

    /**
     * @param non-empty-string $name
     */
    public static function constant(string $name): Type
    {
        return new ConstantType($name);
    }

    /**
     * @no-named-arguments
     */
    public static function intersection(Type $type1, Type $type2, Type ...$moreTypes): Type
    {
        return new IntersectionType([$type1, $type2, ...$moreTypes]);
    }

    /**
     * @no-named-arguments
     * @return Type<int>
     */
    public static function intMask(int $int, int ...$ints): Type
    {
        return new IntMaskType([$int, ...$ints]);
    }

    /**
     * @template TIntMask of int
     * @param Type<TIntMask> $type
     * @return IntMaskOfType<TIntMask>
     */
    public static function intMaskOf(Type $type): IntMaskOfType
    {
        return new IntMaskOfType($type);
    }

    /**
     * @return Type<int>
     */
    public static function intRange(?int $min = null, ?int $max = null): Type
    {
        if ($min === null && $max === null) {
            return self::int;
        }

        return new IntRangeType($min, $max);
    }

    /**
     * @template TKey
     * @template TValue
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return Type<iterable<TKey, TValue>>
     */
    public static function iterable(Type $keyType = self::mixed, Type $valueType = self::mixed): Type
    {
        if ($keyType === self::mixed && $valueType === self::mixed) {
            return self::iterable;
        }

        return new IterableType($keyType, $valueType);
    }

    public static function keyOf(Type $type): Type
    {
        return new KeyOfType($type);
    }

    /**
     * @template TValue
     * @param Type<TValue> $valueType
     * @return Type<list<TValue>>
     */
    public static function list(Type $valueType = self::mixed): Type
    {
        return new ListType($valueType);
    }

    /**
     * @template TValue of bool|int|float|string
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function literal(bool|int|float|string $value): Type
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
        /** @phpstan-ignore-next-line */
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
        /** @phpstan-ignore-next-line */
        return new NonEmptyType(self::list($valueType));
    }

    /**
     * @no-named-arguments
     * @template TType
     * @param Type<TType> $type
     * @return Type<?TType>
     */
    public static function nullable(Type $type): Type
    {
        return new UnionType([self::null, $type]);
    }

    /**
     * @no-named-arguments
     * @template TObject of object
     * @param class-string<TObject>|non-empty-string $class
     * @return ($class is class-string ? Type<TObject> : Type<object>)
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public static function object(string $class, Type ...$templateArguments): Type
    {
        return new NamedObjectType($class, $templateArguments);
    }

    /**
     * @param array<string, Type|Property> $properties
     * @return Type<object>
     */
    public static function objectShape(array $properties = []): Type
    {
        return new ObjectShapeType(array_map(
            static fn(Type|Property $property): Property => $property instanceof Type ? new Property($property) : $property,
            $properties,
        ));
    }

    public static function offset(Type $type, Type $offset): Type
    {
        return new OffsetType($type, $offset);
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
     * @template TType
     * @param non-empty-string $name
     * @param Type<TType> $constraint
     * @return Type<TType>
     */
    public static function template(string $name, AtMethod|AtClass|AtFunction $declaredAt, Type $constraint = self::mixed): Type
    {
        return new TemplateType($name, $declaredAt, $constraint);
    }

    /**
     * @no-named-arguments
     * @template TType
     * @param Type<TType> $type1
     * @param Type<TType> $type2
     * @param Type<TType> ...$moreTypes
     * @return Type<TType>
     */
    public static function union(Type $type1, Type $type2, Type ...$moreTypes): Type
    {
        return new UnionType([$type1, $type2, ...$moreTypes]);
    }

    public static function valueOf(Type $type): Type
    {
        return new ValueOfType($type);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<TType>
     */
    public static function varianceAware(Type $type, Variance $variance): Type
    {
        return new VarianceAwareType($type, $variance);
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return match ($this) {
            self::array => $visitor->array($this, self::arrayKey, self::mixed),
            self::arrayKey => $visitor->union($this, [self::int, self::string]),
            self::bool => $visitor->bool($this),
            self::callable => $visitor->callable($this, [], self::mixed),
            self::classString => $visitor->classString($this),
            self::closure => $visitor->closure($this, [], types::mixed),
            self::false => $visitor->literal($this, false),
            self::float => $visitor->float($this),
            self::int => $visitor->int($this),
            self::iterable => $visitor->iterable($this, self::mixed, self::mixed),
            self::mixed => $visitor->mixed($this),
            self::negativeInt => $visitor->intRange($this, null, -1),
            self::never => $visitor->never($this),
            self::nonNegativeInt => $visitor->intRange($this, 0, null),
            self::nonPositiveInt => $visitor->intRange($this, null, 0),
            self::null => $visitor->null($this),
            self::numericString => $visitor->numericString($this),
            self::object => $visitor->object($this),
            self::positiveInt => $visitor->intRange($this, 1, null),
            self::resource => $visitor->resource($this),
            self::scalar => $visitor->union($this, [self::bool, self::int, self::float, self::string]),
            self::string => $visitor->string($this),
            self::true => $visitor->literal($this, true),
            self::truthyString => $visitor->truthyString($this),
            self::void => $visitor->void($this),
        };
    }
}
