<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\TemplateId;

/**
 * @api
 * @implements Type<mixed>
 */
enum types implements Type
{
    case never;
    case void;
    case null;
    case bool;
    case true;
    case false;
    case int;
    case negativeInt;
    case nonPositiveInt;
    case nonNegativeInt;
    case positiveInt;
    case nonZeroInt;
    case literalInt;
    case float;
    case literalFloat;
    case string;
    case nonEmptyString;
    case numericString;
    case truthyString;
    public const nonFalsyString = self::truthyString;
    case classString;
    case literalString;
    case numeric;
    case resource;
    case arrayKey;
    case array;
    case iterable;
    case object;
    case callable;
    case Closure;
    case scalar;
    case mixed;

    /**
     * @template TValue of int
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function int(int $value): Type
    {
        /** @var Internal\IntType<TValue> */
        return new Internal\IntType($value, $value);
    }

    /**
     * @return Type<int>
     */
    public static function intRange(?int $min = null, ?int $max = null): Type
    {
        return match (true) {
            $min === null && $max === null => self::int,
            $min === null && $max === -1 => self::negativeInt,
            $min === null && $max === 0 => self::nonPositiveInt,
            $min === 0 && $max === null => self::nonNegativeInt,
            $min === 1 && $max === null => self::positiveInt,
            default => new Internal\IntType($min, $max),
        };
    }

    /**
     * @no-named-arguments
     * @param positive-int $value
     * @param positive-int ...$values
     * @return Type<positive-int>
     */
    public static function intMask(int $value, int ...$values): Type
    {
        if ($values === []) {
            return new Internal\IntMaskType(new Internal\IntType($value, $value));
        }

        return new Internal\IntMaskType(new Internal\UnionType(array_map(
            static fn(int $value): Internal\IntType => new Internal\IntType($value, $value),
            [$value, ...$values],
        )));
    }

    /**
     * @return Type<int>
     */
    public static function intMaskOf(Type $type): Type
    {
        return new Internal\IntMaskType($type);
    }

    /**
     * @template TValue of float
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function float(float $value): Type
    {
        /** @var Internal\FloatType<TValue> */
        return new Internal\FloatType($value, $value);
    }

    /**
     * @return Type<float>
     */
    public static function floatRange(?float $min = null, ?float $max = null): Type
    {
        if ($min === null && $max === null) {
            return self::float;
        }

        return new Internal\FloatType($min, $max);
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
     * @template TValue of string
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function string(string $value): Type
    {
        return new Internal\StringValueType($value);
    }

    /**
     * @param non-empty-string|NamedClassId|Type $class
     */
    public static function class(string|NamedClassId|Type $class): Type
    {
        return self::classConstant($class, 'class');
    }

    public static function classString(Type $class): Type
    {
        return new Internal\ClassStringType($class);
    }

    /**
     * @return Type<list<mixed>>
     */
    public static function list(Type $value = self::mixed): Type
    {
        return new Internal\ListType($value, []);
    }

    /**
     * @return Type<list<mixed>>
     */
    public static function nonEmptyList(Type $value = self::mixed): Type
    {
        return new Internal\ListType($value, [new ShapeElement($value)]);
    }

    /**
     * @param list<Type|ShapeElement> $elements
     * @return Type<list<mixed>>
     */
    public static function listShape(array $elements = []): Type
    {
        return self::unsealedListShape($elements, self::never);
    }

    /**
     * @param array<non-negative-int, Type|ShapeElement> $elements
     * @return Type<list<mixed>>
     */
    public static function unsealedListShape(array $elements = [], Type $value = self::mixed): Type
    {
        return new Internal\ListType($value, array_map(
            static fn(Type|ShapeElement $element): ShapeElement => $element instanceof Type ? new ShapeElement($element) : $element,
            $elements,
        ));
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
     * @return Type<non-empty-array<mixed>>
     */
    public static function nonEmptyArray(Type $key = self::arrayKey, Type $value = self::mixed): Type
    {
        return new Internal\NonEmptyArrayType($key, $value);
    }

    /**
     * @param array<Type|ShapeElement> $elements
     * @return Type<array<mixed>>
     */
    public static function arrayShape(array $elements = []): Type
    {
        return self::unsealedArrayShape($elements, self::never, self::never);
    }

    /**
     * @param array<Type|ShapeElement> $elements
     * @return Type<array<mixed>>
     */
    public static function unsealedArrayShape(array $elements = [], Type $key = self::arrayKey, Type $value = self::mixed): Type
    {
        return new Internal\ArrayType($key, $value, array_map(
            static fn(Type|ShapeElement $element): ShapeElement => $element instanceof Type ? new ShapeElement($element) : $element,
            $elements,
        ));
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

    public static function keyOf(Type $array): Type
    {
        return new Internal\KeyType($array);
    }

    public static function valueOf(Type $array): Type
    {
        return self::offset($array, self::keyOf($array));
    }

    public static function offset(Type $array, Type $key): Type
    {
        return new Internal\OffsetType($array, $key);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return ShapeElement<TType>
     */
    public static function optional(Type $type): ShapeElement
    {
        return new ShapeElement($type, true);
    }

    /**
     * @param non-empty-string|NamedClassId $class
     * @param list<Type> $arguments
     * @return Type<object>
     */
    public static function object(string|NamedClassId $class, array $arguments = []): Type
    {
        if (\is_string($class)) {
            $class = Id::namedClass($class);
        }

        if ($class->name === \Closure::class && $arguments === []) {
            return self::Closure;
        }

        return new Internal\NamedObjectType($class, $arguments);
    }

    public static function Generator(Type $key = self::mixed, Type $value = self::mixed, Type $send = self::mixed, Type $return = self::mixed): Type
    {
        return new Internal\NamedObjectType(Id::namedClass(\Generator::class), [$key, $value, $send, $return]);
    }

    /**
     * @param array<string, Type|ShapeElement> $properties
     * @return Type<object>
     */
    public static function objectShape(array $properties = []): Type
    {
        if ($properties === []) {
            return self::object;
        }

        return new Internal\ObjectType(array_map(
            static fn(Type|ShapeElement $property): ShapeElement => $property instanceof Type ? new ShapeElement($property) : $property,
            $properties,
        ));
    }

    /**
     * @param list<Type> $arguments
     * @param null|non-empty-string|NamedClassId|AnonymousClassId $resolvedClass
     */
    public static function self(array $arguments = [], null|string|NamedClassId|AnonymousClassId $resolvedClass = null): Type
    {
        if (\is_string($resolvedClass)) {
            $resolvedClass = Id::class($resolvedClass);
        }

        return new Internal\SelfType($arguments, $resolvedClass);
    }

    /**
     * @param list<Type> $arguments
     * @param null|non-empty-string|NamedClassId $resolvedClass
     */
    public static function parent(array $arguments = [], null|string|NamedClassId $resolvedClass = null): Type
    {
        if (\is_string($resolvedClass)) {
            $resolvedClass = Id::namedClass($resolvedClass);
        }

        return new Internal\ParentType($arguments, $resolvedClass);
    }

    /**
     * @param list<Type> $arguments
     * @param null|non-empty-string|NamedClassId|AnonymousClassId $resolvedClass
     */
    public static function static(array $arguments = [], null|string|NamedClassId|AnonymousClassId $resolvedClass = null): Type
    {
        if (\is_string($resolvedClass)) {
            $resolvedClass = Id::class($resolvedClass);
        }

        return new Internal\StaticType($arguments, $resolvedClass);
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
     * @template TReturn
     * @param list<Type|Parameter> $parameters
     * @param Type<TReturn> $return
     */
    public static function callableString(array $parameters = [], Type $return = self::mixed): Type
    {
        return self::intersection(self::string, self::callable($parameters, $return));
    }

    /**
     * @template TReturn
     * @param list<Type|Parameter> $parameters
     * @param Type<TReturn> $return
     */
    public static function callableArray(array $parameters = [], Type $return = self::mixed): Type
    {
        return self::intersection(self::list(), self::callable($parameters, $return));
    }

    /**
     * @param list<Type|Parameter> $parameters
     * @return Type<\Closure>
     */
    public static function Closure(array $parameters = [], Type $return = self::mixed): Type
    {
        if ($parameters === [] && $return === self::mixed) {
            return self::Closure;
        }

        return new Internal\IntersectionType([
            self::Closure,
            new Internal\CallableType(
                array_map(
                    static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type ? new Parameter($parameter) : $parameter,
                    $parameters,
                ),
                $return,
            ),
        ]);
    }

    /**
     * @template TType
     * @param Type<TType> $type
     * @return Parameter<TType>
     */
    public static function param(Type $type = self::mixed, bool $hasDefault = false, bool $variadic = false, bool $byReference = false): Parameter
    {
        return new Parameter($type, $hasDefault, $variadic, $byReference);
    }

    /**
     * @param non-empty-string|ConstantId $name
     */
    public static function constant(string|ConstantId $name): Type
    {
        if (!$name instanceof ConstantId) {
            $name = Id::constant($name);
        }

        return new Internal\ConstantType($name);
    }

    /**
     * @param non-empty-string|NamedClassId|Type $class
     * @param non-empty-string $name
     */
    public static function classConstant(string|NamedClassId|Type $class, string $name): Type
    {
        if (!$class instanceof Type) {
            $class = self::object($class);
        }

        if (str_ends_with($name, '*')) {
            return new Internal\ClassConstantMaskType($class, substr($name, 0, -1));
        }

        return new Internal\ClassConstantType($class, $name);
    }

    /**
     * @param non-empty-string|NamedClassId|Type $class
     */
    public static function classConstantMask(string|NamedClassId|Type $class, string $namePrefix = ''): Type
    {
        if (!$class instanceof Type) {
            $class = self::object($class);
        }

        return new Internal\ClassConstantMaskType($class, $namePrefix);
    }

    /**
     * @param list<Type> $arguments
     */
    public static function alias(AliasId $alias, array $arguments = []): Type
    {
        return new Internal\AliasType($alias, $arguments);
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $name
     * @param list<Type> $arguments
     */
    public static function classAlias(string|NamedClassId|AnonymousClassId $class, string $name, array $arguments = []): Type
    {
        return new Internal\AliasType(Id::alias($class, $name), $arguments);
    }

    public static function template(TemplateId $id): Type
    {
        return new Internal\TemplateType($id);
    }

    /**
     * @param non-empty-string|NamedFunctionId|AnonymousFunctionId $function
     * @param non-empty-string $name
     */
    public static function functionTemplate(string|NamedFunctionId|AnonymousFunctionId $function, string $name): Type
    {
        if (\is_string($function)) {
            $function = Id::namedFunction($function);
        }

        return new Internal\TemplateType(Id::template($function, $name));
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $name
     */
    public static function classTemplate(string|NamedClassId|AnonymousClassId $class, string $name): Type
    {
        if (\is_string($class)) {
            $class = Id::class($class);
        }

        return new Internal\TemplateType(Id::template($class, $name));
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     */
    public static function methodTemplate(string|NamedClassId|AnonymousClassId $class, string $method, string $name): Type
    {
        return new Internal\TemplateType(Id::template(Id::method($class, $method), $name));
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

    /**
     * @no-named-arguments
     * @template TType
     * @param Type<TType> ...$types
     * @return Type<TType>
     */
    public static function union(Type ...$types): Type
    {
        return match (\count($types)) {
            0 => self::never,
            1 => $types[0],
            default => new Internal\UnionType($types),
        };
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

    public static function scalar(bool|int|float|string $value): Type
    {
        /** @psalm-suppress PossiblyInvalidArgument */
        return match (true) {
            $value === true => self::true,
            $value === false => self::false,
            \is_int($value) => new Internal\IntType($value, $value),
            \is_float($value) => new Internal\FloatType($value, $value),
            default => new Internal\StringValueType($value),
        };
    }

    public static function conditional(Type $subject, Type $if, Type $then, Type $else): Type
    {
        return new Internal\ConditionalType($subject, $if, $then, $else);
    }

    public static function arg(ParameterId $parameter): Type
    {
        return new Internal\ArgumentType($parameter);
    }

    /**
     * @param non-empty-string|NamedFunctionId|AnonymousFunctionId $function
     * @param non-empty-string $name
     */
    public static function functionArg(string|NamedFunctionId|AnonymousFunctionId $function, string $name): Type
    {
        if (\is_string($function)) {
            $function = Id::namedFunction($function);
        }

        return new Internal\ArgumentType(Id::parameter($function, $name));
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     */
    public static function methodArg(string|NamedClassId|AnonymousClassId $class, string $method, string $name): Type
    {
        return new Internal\ArgumentType(Id::parameter(Id::method($class, $method), $name));
    }

    /**
     * @no-named-arguments
     */
    public static function intersection(Type ...$types): Type
    {
        return match (\count($types)) {
            0 => self::never,
            1 => $types[0],
            default => new Internal\IntersectionType($types),
        };
    }

    public static function not(Type $type): Type
    {
        return new Internal\NotType($type);
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        // most frequently used types should go first
        return match ($this) {
            self::null => $visitor->null($this),
            self::true => $visitor->true($this),
            self::false => $visitor->false($this),
            self::bool => $visitor->union($this, [self::true, self::false]),
            self::int => $visitor->int($this, null, null),
            self::float => $visitor->float($this, null, null),
            self::string => $visitor->string($this),
            self::array => $visitor->array($this, self::arrayKey, self::mixed, []),
            self::iterable => $visitor->iterable($this, self::mixed, self::mixed),
            self::object => $visitor->object($this, []),
            self::mixed => $visitor->mixed($this),
            self::void => $visitor->void($this),
            self::never => $visitor->never($this),
            self::callable => $visitor->callable($this, [], self::mixed),
            self::Closure => $visitor->namedObject($this, Id::namedClass(\Closure::class), []),
            self::nonEmptyString => $visitor->intersection($this, [
                self::string,
                new Internal\NotType(new Internal\StringValueType('')),
            ]),
            self::resource => $visitor->resource($this),
            self::negativeInt => $visitor->int($this, null, -1),
            self::nonPositiveInt => $visitor->int($this, null, 0),
            self::nonNegativeInt => $visitor->int($this, 0, null),
            self::positiveInt => $visitor->int($this, 1, null),
            self::classString => $visitor->classString($this, types::object),
            self::arrayKey => $visitor->union($this, [self::int, self::string]),
            self::numeric => $visitor->numeric($this),
            self::numericString => $visitor->intersection($this, [self::string, self::numeric]),
            self::scalar => $visitor->union($this, [self::bool, self::int, self::float, self::string]),
            self::truthyString => $visitor->intersection($this, [
                self::string,
                new Internal\NotType(new Internal\StringValueType('')),
                new Internal\NotType(new Internal\StringValueType('0')),
            ]),
            self::literalInt => $visitor->literal($this, self::int),
            self::literalFloat => $visitor->literal($this, self::float),
            self::literalString => $visitor->literal($this, self::string),
            self::nonZeroInt => $visitor->union($this, [self::positiveInt, self::negativeInt]),
        };
    }
}
