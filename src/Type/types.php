<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
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
    public static function alias(string $class, string $name, Type ...$arguments): Type
    {
        if (!array_is_list($arguments)) {
            trigger_deprecation('typhoon/type', '0.3.1', 'Calling %s() with named arguments is deprecated.', __METHOD__);
            /** @var list<Type> */
            $arguments = array_values($arguments);
        }

        return new Internal\AliasType($class, $name, $arguments);
    }

    /**
     * @param non-empty-string $name
     */
    public static function arg(string $name): Argument
    {
        return new Argument($name);
    }

    /**
     * @return Type<array<mixed>>
     */
    public static function array(Type $key = self::arrayKey, Type $value = self::mixed): Type
    {
        if ($key === self::arrayKey && $value === self::mixed) {
            return self::array;
        }

        return new Internal\ArrayType($key, $value, []);
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
    public static function arrayShape(array $elements = [], Type $key = self::arrayKey, Type $value = self::never): Type
    {
        return new Internal\ArrayType($key, $value, array_map(
            static fn(Type|ArrayElement $element): ArrayElement => $element instanceof Type ? new ArrayElement($element) : $element,
            $elements,
        ));
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

        return new Internal\CallableType(
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
        return new Internal\ClassConstantType($class, $name);
    }

    /**
     * @template TObject of object
     * @return ($object is Type<TObject> ? Type<class-string<TObject>> : Type<non-empty-string>)
     */
    public static function classString(Type $object): Type
    {
        return new Internal\ClassStringType($object);
    }

    /**
     * @template TClass of non-empty-string
     * @param TClass $class
     * @return Type<TClass>
     */
    public static function classStringLiteral(string $class): Type
    {
        return new Internal\ClassStringLiteralType($class);
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

        return new Internal\ClosureType(
            array_map(
                static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type ? new Parameter($parameter) : $parameter,
                $parameters,
            ),
            $return,
        );
    }

    public static function conditional(Argument|Type $subject, Type $if, Type $then, Type $else): Type
    {
        return new Internal\ConditionalType($subject, $if, $then, $else);
    }

    /**
     * @param non-empty-string $name
     */
    public static function constant(string $name): Type
    {
        return new Internal\ConstantType($name);
    }

    public static function intersection(Type ...$types): Type
    {
        return match (\count($types)) {
            0 => self::never,
            1 => $types[array_key_first($types)],
            /** @phpstan-ignore argument.type */
            default => new Internal\IntersectionType(array_values($types)),
        };
    }

    /**
     * @return Type<int>
     */
    public static function intMask(Type $type): Type
    {
        return new Internal\IntMaskType($type);
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

        return new Internal\IntRangeType($min, $max);
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

        return new Internal\IterableType($key, $value);
    }

    public static function key(Type $type): Type
    {
        return new Internal\KeyType($type);
    }

    /**
     * @return Type<list<mixed>>
     */
    public static function list(Type $value = self::mixed): Type
    {
        return new Internal\ListType($value, []);
    }

    /**
     * @param array<int, Type|ArrayElement> $elements
     * @return Type<list<mixed>>
     */
    public static function listShape(array $elements = [], Type $value = self::never): Type
    {
        return new Internal\ListType($value, array_map(
            static fn(Type|ArrayElement $element): ArrayElement => $element instanceof Type ? new ArrayElement($element) : $element,
            $elements,
        ));
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<TType>
     */
    public static function literal(Type $type): Type
    {
        return new Internal\LiteralType($type);
    }

    /**
     * @template TValue of bool|int|float|string
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function literalValue(bool|int|float|string $value): Type
    {
        return new Internal\LiteralValueType($value);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<TType>
     */
    public static function nonEmpty(Type $type): Type
    {
        return new Internal\NonEmptyType($type);
    }

    /**
     * @return Type<non-empty-array<mixed>>
     * @psalm-suppress MoreSpecificReturnType, LessSpecificReturnStatement
     */
    public static function nonEmptyArray(Type $key = self::arrayKey, Type $value = self::mixed): Type
    {
        /** @phpstan-ignore return.type */
        return new Internal\NonEmptyType(self::array($key, $value));
    }

    /**
     * @return Type<non-empty-list<mixed>>
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public static function nonEmptyList(Type $value = self::mixed): Type
    {
        /** @phpstan-ignore return.type */
        return new Internal\NonEmptyType(self::list($value));
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<?TType>
     */
    public static function nullable(Type $type): Type
    {
        return new Internal\UnionType([self::null, $type]);
    }

    /**
     * @template TObject of object
     * @param class-string<TObject>|non-empty-string $class
     * @return ($class is class-string ? Type<TObject> : Type<object>)
     */
    public static function object(string $class, Type ...$arguments): Type
    {
        if ($class === \Closure::class && $arguments === []) {
            return self::closure;
        }

        if (!array_is_list($arguments)) {
            trigger_deprecation('typhoon/type', '0.3.1', 'Calling %s() with named arguments is deprecated.', __METHOD__);
            /** @var list<Type> */
            $arguments = array_values($arguments);
        }

        return new Internal\NamedObjectType($class, $arguments);
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

        return new Internal\ObjectShapeType(array_map(
            static fn(Type|Property $property): Property => $property instanceof Type ? new Property($property) : $property,
            $properties,
        ));
    }

    public static function offset(Type $type, Type $offset): Type
    {
        return new Internal\OffsetType($type, $offset);
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
     * @param non-empty-string $name
     */
    public static function template(string $name, AtMethod|AtClass|AtFunction $declaredAt, Type ...$arguments): Type
    {
        if (!array_is_list($arguments)) {
            trigger_deprecation('typhoon/type', '0.3.1', 'Calling %s() with named arguments is deprecated.', __METHOD__);
            /** @var list<Type> */
            $arguments = array_values($arguments);
        }

        return new Internal\TemplateType($name, $declaredAt, $arguments);
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
            default => new Internal\UnionType(array_values($types)),
        };
    }

    public static function value(Type $type): Type
    {
        return self::offset($type, self::key($type));
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Type<TType>
     */
    public static function varianceAware(Type $type, Variance $variance): Type
    {
        return new Internal\VarianceAwareType($type, $variance);
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return match ($this) {
            self::array => $visitor->array($this, self::arrayKey, self::mixed, []),
            self::arrayKey => $visitor->union($this, [self::int, self::string]),
            self::bool => $visitor->bool($this),
            self::callable => $visitor->callable($this, [], self::mixed),
            self::classString => $visitor->classString($this, types::object),
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
