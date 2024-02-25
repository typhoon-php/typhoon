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
    case literalInt;
    case literalString;
    case mixed;
    case negativeInt;
    case never;
    case nonEmptyString;
    case nonNegativeInt;
    case nonPositiveInt;
    case null;
    case numeric;
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
     * @param non-empty-string $name
     */
    public static function arg(string $name): Argument
    {
        return new Argument($name);
    }

    /**
     * @template TKey
     * @template TValue
     * @param Type<TKey> $key
     * @param Type<TValue> $value
     * @return Type<array<TKey, TValue>>
     */
    public static function array(Type $key = self::arrayKey, Type $value = self::mixed): Type
    {
        if ($key === self::arrayKey && $value === self::mixed) {
            return self::array;
        }

        return new ArrayType($key, $value);
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
        if (!$sealed && $elements === []) {
            return self::array;
        }

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
     * @param Type<TReturn> $return
     * @return Type<callable>
     */
    public static function callable(array $parameters = [], Type $return = self::mixed): Type
    {
        if ($parameters === [] && $return === self::mixed) {
            return self::callable;
        }

        return new CallableType(
            array_map(
                static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type ? new Parameter($parameter) : $parameter,
                $parameters,
            ),
            $return,
        );
    }

    /**
     * @param non-empty-string $name
     */
    public static function classConstant(Type $class, string $name): Type
    {
        return new ClassConstantType($class, $name);
    }

    /**
     * @template TObject of object
     * @return ($object is Type<TObject> ? Type<class-string<TObject>> : Type<non-empty-string>)
     */
    public static function classString(Type $object): Type
    {
        return new NamedClassStringType($object);
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
     * @param list<Type|Parameter> $parameters
     * @return Type<\Closure>
     */
    public static function closure(array $parameters = [], Type $return = self::mixed): Type
    {
        if ($parameters === [] && $return === self::mixed) {
            return self::closure;
        }

        return new ClosureType(
            array_map(
                static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type ? new Parameter($parameter) : $parameter,
                $parameters,
            ),
            $return,
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

    public static function intersection(Type ...$types): Type
    {
        return match (\count($types)) {
            0 => self::never,
            1 => $types[array_key_first($types)],
            /** @phpstan-ignore argument.type */
            default => new UnionType(array_values($types)),
        };
    }

    /**
     * @return Type<int>
     */
    public static function intMask(Type $type): Type
    {
        return new IntMaskType($type);
    }

    /**
     * @return Type<int>
     */
    public static function intRange(?int $min = null, ?int $max = null): Type
    {
        if ($min === null && $max === null) {
            return self::int;
        }

        if ($min === $max) {
            /** @var int $min */
            return self::literalValue($min);
        }

        return new IntRangeType($min, $max);
    }

    /**
     * @template TKey
     * @template TValue
     * @param Type<TKey> $key
     * @param Type<TValue> $value
     * @return Type<iterable<TKey, TValue>>
     */
    public static function iterable(Type $key = self::mixed, Type $value = self::mixed): Type
    {
        if ($key === self::mixed && $value === self::mixed) {
            return self::iterable;
        }

        return new IterableType($key, $value);
    }

    public static function key(Type $type): Type
    {
        return new KeyType($type);
    }

    /**
     * @template TValue
     * @param Type<TValue> $value
     * @return Type<list<TValue>>
     */
    public static function list(Type $value = self::mixed): Type
    {
        return new ListType($value);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<TType>
     */
    public static function literal(Type $type): Type
    {
        return new LiteralType($type);
    }

    /**
     * @template TValue of bool|int|float|string
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function literalValue(bool|int|float|string $value): Type
    {
        return new LiteralValueType($value);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<TType>
     */
    public static function nonEmpty(Type $type): Type
    {
        return new NonEmptyType($type);
    }

    /**
     * @template TKey
     * @template TValue
     * @param Type<TKey> $key
     * @param Type<TValue> $value
     * @return Type<non-empty-array<TKey, TValue>>
     * @psalm-suppress MoreSpecificReturnType, LessSpecificReturnStatement
     */
    public static function nonEmptyArray(Type $key = self::arrayKey, Type $value = self::mixed): Type
    {
        /** @phpstan-ignore return.type */
        return new NonEmptyType(self::array($key, $value));
    }

    /**
     * @template TValue
     * @param Type<TValue> $value
     * @return Type<non-empty-list<TValue>>
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public static function nonEmptyList(Type $value = self::mixed): Type
    {
        /** @phpstan-ignore return.type */
        return new NonEmptyType(self::list($value));
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<?TType>
     */
    public static function nullable(Type $type): Type
    {
        return new UnionType([self::null, $type]);
    }

    /**
     * @template TObject of object
     * @param class-string<TObject>|non-empty-string $class
     * @return ($class is class-string ? Type<TObject> : Type<object>)
     */
    public static function object(string $class, Type ...$templateArguments): Type
    {
        return new NamedObjectType($class, array_values($templateArguments));
    }

    /**
     * @param array<string, Type|Property> $properties
     * @return Type<object>
     */
    public static function objectShape(array $properties = []): Type
    {
        if ($properties === []) {
            return self::object;
        }

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
     * @template TType
     * @param Type<TType> ...$types
     * @return Type<TType>
     */
    public static function union(Type ...$types): Type
    {
        return match (\count($types)) {
            0 => self::never,
            1 => $types[array_key_first($types)],
            /** @phpstan-ignore argument.type */
            default => new UnionType(array_values($types)),
        };
    }

    public static function value(Type $type): Type
    {
        return new ValueType($type);
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
            self::false => $visitor->literalValue($this, false),
            self::float => $visitor->float($this),
            self::int => $visitor->int($this),
            self::iterable => $visitor->iterable($this, self::mixed, self::mixed),
            self::literalInt => $visitor->literal($this, self::int),
            self::literalString => $visitor->literal($this, self::string),
            self::mixed => $visitor->mixed($this),
            self::negativeInt => $visitor->intRange($this, null, -1),
            self::never => $visitor->never($this),
            self::nonEmptyString => $visitor->nonEmpty($this, self::string),
            self::nonNegativeInt => $visitor->intRange($this, 0, null),
            self::nonPositiveInt => $visitor->intRange($this, null, 0),
            self::null => $visitor->null($this),
            self::numeric => $visitor->union($this, [self::int, self::float, self::numericString]),
            self::numericString => $visitor->numericString($this),
            self::object => $visitor->object($this),
            self::positiveInt => $visitor->intRange($this, 1, null),
            self::resource => $visitor->resource($this),
            self::scalar => $visitor->union($this, [self::bool, self::int, self::float, self::string]),
            self::string => $visitor->string($this),
            self::true => $visitor->literalValue($this, true),
            self::truthyString => $visitor->truthyString($this),
            self::void => $visitor->void($this),
        };
    }
}
