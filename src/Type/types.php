<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\DeclarationId\FunctionId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\TemplateId;
use function Typhoon\DeclarationId\classId;
use function Typhoon\DeclarationId\namedClassId;

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
    case literalFloat;
    case literalString;
    case lowercaseString;
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
     * @no-named-arguments
     */
    public static function alias(AliasId $alias, Type ...$arguments): Type
    {
        return new Internal\AliasType($alias, $arguments);
    }

    /**
     * @no-named-arguments
     */
    public static function classAlias(string|NamedClassId $class, string $name, Type ...$arguments): Type
    {
        return new Internal\AliasType(DeclarationId::alias($class, $name), $arguments);
    }

    /**
     * @param non-empty-string $name
     */
    public static function arg(string $name): Argument
    {
        return new Argument($name);
    }

    /**
     * @return Type<non-empty-array<mixed>>
     */
    public static function nonEmptyArray(Type $key = self::arrayKey, Type $value = self::mixed): Type
    {
        return new Internal\NonEmptyArrayType($key, $value);
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
    public static function classConstant(string|ClassId|Type $class, string $name): Type
    {
        if (!$class instanceof Type) {
            $class = self::object($class);
        }

        return new Internal\ClassConstantType($class, $name);
    }

    public static function classString(Type $object): Type
    {
        return new Internal\ClassStringType($object);
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

        return new Internal\IntersectionType([
            self::closure,
            new Internal\CallableType(
                array_map(
                    static fn(Type|Parameter $parameter): Parameter => $parameter instanceof Type ? new Parameter($parameter) : $parameter,
                    $parameters,
                ),
                $return,
            ),
        ]);
    }

    public static function conditional(Argument|Type $subject, Type $if, Type $then, Type $else): Type
    {
        return new Internal\ConditionalType($subject, $if, $then, $else);
    }

    public static function constant(string|ConstantId $name): Type
    {
        if (!$name instanceof ConstantId) {
            $name = DeclarationId::constant($name);
        }

        return new Internal\ConstantType($name);
    }

    public static function intersection(Type ...$types): Type
    {
        return match (\count($types)) {
            0 => self::never,
            1 => $types[array_key_first($types)],
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
    public static function int(?int $min = null, ?int $max = null): Type
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
     * @param array<non-negative-int, Type|ArrayElement> $elements
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
     * @template TValue of int
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function intValue(int $value): Type
    {
        /** @var Internal\IntType<TValue> */
        return new Internal\IntType($value, $value);
    }

    /**
     * @template TValue of float
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function floatValue(float $value): Type
    {
        return new Internal\FloatValueType($value);
    }

    /**
     * @template TValue of string
     * @param TValue $value
     * @return Type<TValue>
     */
    public static function stringValue(string $value): Type
    {
        return new Internal\StringValueType($value);
    }

    /**
     * @return Type<non-empty-list<mixed>>
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public static function nonEmptyList(Type $value = self::mixed): Type
    {
        /** @phpstan-ignore return.type */
        return new Internal\ListType($value, [new ArrayElement($value)]);
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
     * @no-named-arguments
     * @return Type<object>
     */
    public static function object(string|ClassId $class, Type ...$arguments): Type
    {
        if (\is_string($class)) {
            $class = classId($class);
        }

        if (!$class instanceof AnonymousClassId && $class->name === \Closure::class) {
            \assert($arguments === [], 'Closure type arguments are not supported');

            return self::closure;
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

        return new Internal\ObjectType(array_map(
            static fn(Type|Property $property): Property => $property instanceof Type ? new Property($property) : $property,
            $properties,
        ));
    }

    public static function generator(Type $key = self::mixed, Type $value = self::mixed, Type $send = self::mixed, Type $return = self::mixed): Type
    {
        return new Internal\NamedObjectType(DeclarationId::namedClass(\Generator::class), [$key, $value, $send, $return]);
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
     * @no-named-arguments
     */
    public static function self(null|string|ClassId $resolvedClass = null, Type ...$arguments): Type
    {
        if (\is_string($resolvedClass)) {
            $resolvedClass = classId($resolvedClass);
        }

        return new Internal\SelfType($resolvedClass, $arguments);
    }

    /**
     * @no-named-arguments
     */
    public static function parent(null|string|NamedClassId $resolvedClass = null, Type ...$arguments): Type
    {
        if (\is_string($resolvedClass)) {
            $resolvedClass = namedClassId($resolvedClass);
        }

        return new Internal\ParentType($resolvedClass, $arguments);
    }

    /**
     * @no-named-arguments
     */
    public static function static(null|string|ClassId $resolvedClass = null, Type ...$arguments): Type
    {
        if (\is_string($resolvedClass)) {
            $resolvedClass = classId($resolvedClass);
        }

        return new Internal\StaticType($resolvedClass, $arguments);
    }

    public static function template(TemplateId $id): Type
    {
        return new Internal\TemplateType($id);
    }

    public static function functionTemplate(string|FunctionId $function, string $name): Type
    {
        if (!$function instanceof FunctionId) {
            $function = DeclarationId::namedFunction($function);
        }

        return new Internal\TemplateType(DeclarationId::template($function, $name));
    }

    public static function classTemplate(string|ClassId $class, string $name): Type
    {
        if (!$class instanceof ClassId) {
            $class = DeclarationId::class($class);
        }

        return new Internal\TemplateType(DeclarationId::template($class, $name));
    }

    public static function methodTemplate(string|ClassId $class, string $method, string $name): Type
    {
        return new Internal\TemplateType(DeclarationId::template(DeclarationId::method($class, $method), $name));
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

    public static function not(Type $type): Type
    {
        return new Internal\NotType($type);
    }

    /**
     * @todo split to different enums?
     */
    public function accept(TypeVisitor $visitor): mixed
    {
        return match ($this) {
            self::array => $visitor->array($this, self::arrayKey, self::mixed, []),
            self::arrayKey => $visitor->union($this, [self::int, self::string]),
            self::bool => $visitor->union($this, [self::true, self::false]),
            self::callable => $visitor->callable($this, [], self::mixed),
            self::classString => $visitor->classString($this, types::object),
            self::closure => $visitor->namedObject($this, DeclarationId::class(\Closure::class), []),
            self::false => $visitor->false($this),
            self::float => $visitor->float($this),
            self::int => $visitor->int($this, null, null),
            self::iterable => $visitor->iterable($this, self::mixed, self::mixed),
            self::literalInt => $visitor->literal($this, self::int),
            self::literalFloat => $visitor->literal($this, self::float),
            self::literalString => $visitor->literal($this, self::string),
            self::mixed => $visitor->mixed($this),
            self::negativeInt => $visitor->int($this, null, -1),
            self::never => $visitor->never($this),
            self::nonEmptyString => $visitor->intersection($this, [
                self::string,
                new Internal\NotType(new Internal\StringValueType('')),
            ]),
            self::nonNegativeInt => $visitor->int($this, 0, null),
            self::nonPositiveInt => $visitor->int($this, null, 0),
            self::null => $visitor->null($this),
            self::numeric => $visitor->numeric($this),
            self::numericString => $visitor->intersection($this, [self::string, self::numeric]),
            self::object => $visitor->object($this, []),
            self::positiveInt => $visitor->int($this, 1, null),
            self::resource => $visitor->resource($this),
            self::scalar => $visitor->union($this, [self::bool, self::int, self::float, self::string]),
            self::string => $visitor->string($this),
            self::lowercaseString => $visitor->lowercaseString($this),
            self::true => $visitor->true($this),
            self::truthyString => $visitor->intersection($this, [
                self::string,
                new Internal\NotType(new Internal\StringValueType('')),
                new Internal\NotType(new Internal\StringValueType('0')),
            ]),
            self::void => $visitor->void($this),
        };
    }
}
