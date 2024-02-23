<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TReturn
 */
interface TypeVisitor
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     * @return TReturn
     */
    public function alias(Type $type, string $class, string $name): mixed;

    /**
     * @template TType
     * @param Type<TType> $type
     * @param Type<TType> $innerType
     * @return TReturn
     */
    public function anyLiteral(Type $type, Type $innerType): mixed;

    /**
     * @template TKey of array-key
     * @template TValue
     * @param Type<array<TKey, TValue>> $type
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return TReturn
     */
    public function array(Type $type, Type $keyType, Type $valueType): mixed;

    /**
     * @param Type<array<mixed>> $type
     * @param array<ArrayElement> $elements
     * @return TReturn
     */
    public function arrayShape(Type $type, array $elements, bool $sealed): mixed;

    /**
     * @param Type<bool> $type
     * @return TReturn
     */
    public function bool(Type $type): mixed;

    /**
     * @template TCallableReturn
     * @param Type<callable(): TCallableReturn> $type
     * @param list<Parameter> $parameters
     * @param ?Type<TCallableReturn> $returnType
     * @return TReturn
     */
    public function callable(Type $type, array $parameters, ?Type $returnType): mixed;

    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     * @return TReturn
     */
    public function classConstant(Type $type, string $class, string $name): mixed;

    /**
     * @param Type<class-string> $type
     * @return TReturn
     */
    public function classString(Type $type): mixed;

    /**
     * @template TClass of non-empty-string
     * @param Type<TClass> $type
     * @param TClass $class
     * @return TReturn
     */
    public function classStringLiteral(Type $type, string $class): mixed;

    /**
     * @template TCallableReturn
     * @param Type<\Closure(): TCallableReturn> $type
     * @param list<Parameter> $parameters
     * @param ?Type<TCallableReturn> $returnType
     * @return TReturn
     */
    public function closure(Type $type, array $parameters, ?Type $returnType): mixed;

    /**
     * @return TReturn
     */
    public function conditional(Type $type, Argument|TemplateType $subject, Type $if, Type $then, Type $else): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function constant(Type $type, string $name): mixed;

    /**
     * @param Type<float> $type
     * @return TReturn
     */
    public function float(Type $type): mixed;

    /**
     * @param Type<int> $type
     * @return TReturn
     */
    public function int(Type $type): mixed;

    /**
     * @param non-empty-list<Type> $types
     * @return TReturn
     */
    public function intersection(Type $type, array $types): mixed;

    /**
     * @param Type<int> $type
     * @param non-empty-list<int> $ints
     * @return TReturn
     */
    public function intMask(Type $type, array $ints): mixed;

    /**
     * @template TIntMask of int
     * @param Type<TIntMask> $type
     * @param Type<TIntMask> $innerType
     * @return TReturn
     */
    public function intMaskOf(Type $type, Type $innerType): mixed;

    /**
     * @param Type<int> $type
     * @return TReturn
     */
    public function intRange(Type $type, ?int $min, ?int $max): mixed;

    /**
     * @template TKey
     * @template TValue
     * @param Type<iterable<TKey, TValue>> $type
     * @param Type<TKey> $keyType
     * @param Type<TValue> $valueType
     * @return TReturn
     */
    public function iterable(Type $type, Type $keyType, Type $valueType): mixed;

    /**
     * @return TReturn
     */
    public function keyOf(Type $type, Type $innerType): mixed;

    /**
     * @template TValue
     * @param Type<list<TValue>> $type
     * @param Type<TValue> $valueType
     * @return TReturn
     */
    public function list(Type $type, Type $valueType): mixed;

    /**
     * @template TValue of bool|int|float|string
     * @param Type<TValue> $type
     * @param TValue $value
     * @return TReturn
     */
    public function literal(Type $type, bool|int|float|string $value): mixed;

    /**
     * @return TReturn
     */
    public function mixed(Type $type): mixed;

    /**
     * @template TObject
     * @param Type<class-string<TObject>> $type
     * @param Type<TObject> $objectType
     * @return TReturn
     */
    public function namedClassString(Type $type, Type $objectType): mixed;

    /**
     * @template TObject of object
     * @param Type<TObject> $type
     * @param non-empty-string|class-string<TObject> $class
     * @param list<Type> $templateArguments
     * @return TReturn
     */
    public function namedObject(Type $type, string $class, array $templateArguments): mixed;

    /**
     * @param Type<never> $type
     * @return TReturn
     */
    public function never(Type $type): mixed;

    /**
     * @template TType
     * @param Type<TType> $type
     * @param Type<TType> $innerType
     * @return TReturn
     */
    public function nonEmpty(Type $type, Type $innerType): mixed;

    /**
     * @param Type<null> $type
     * @return TReturn
     */
    public function null(Type $type): mixed;

    /**
     * @param Type<numeric-string> $type
     * @return TReturn
     */
    public function numericString(Type $type): mixed;

    /**
     * @param Type<object> $type
     * @return TReturn
     */
    public function object(Type $type): mixed;

    /**
     * @param Type<object> $type
     * @param array<string, Property> $properties
     * @return TReturn
     */
    public function objectShape(Type $type, array $properties): mixed;

    /**
     * @return TReturn
     */
    public function offset(Type $type, Type $innerType, Type $offset): mixed;

    /**
     * @param Type<resource> $type
     * @return TReturn
     */
    public function resource(Type $type): mixed;

    /**
     * @param Type<string> $type
     * @return TReturn
     */
    public function string(Type $type): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function template(Type $type, string $name, AtFunction|AtClass|AtMethod $declaredAt, Type $constraint): mixed;

    /**
     * @param Type<truthy-string> $type
     * @return TReturn
     */
    public function truthyString(Type $type): mixed;

    /**
     * @template TType
     * @param Type<TType> $type
     * @param non-empty-list<Type<TType>> $types
     * @return TReturn
     */
    public function union(Type $type, array $types): mixed;

    /**
     * @return TReturn
     */
    public function valueOf(Type $type, Type $innerType): mixed;

    /**
     * @template TType
     * @param Type<TType> $type
     * @param Type<TType> $innerType
     * @return TReturn
     */
    public function varianceAware(Type $type, Type $innerType, Variance $variance): mixed;

    /**
     * @param Type<void> $type
     * @return TReturn
     */
    public function void(Type $type): mixed;
}
