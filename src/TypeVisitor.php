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
     * @return TReturn
     */
    public function anyLiteral(Type $type, Type $innerType): mixed;

    /**
     * @param Type<array<mixed>> $type
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
     * @param Type<callable> $type
     * @param list<Parameter> $parameters
     * @return TReturn
     */
    public function callable(Type $type, array $parameters, Type $returnType): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function classConstant(Type $type, Type $classType, string $name): mixed;

    /**
     * @param Type<class-string> $type
     * @return TReturn
     */
    public function classString(Type $type): mixed;

    /**
     * @param Type<non-empty-string> $type
     * @param non-empty-string $class
     * @return TReturn
     */
    public function classStringLiteral(Type $type, string $class): mixed;

    /**
     * @param Type<\Closure> $type
     * @param list<Parameter> $parameters
     * @return TReturn
     */
    public function closure(Type $type, array $parameters, Type $returnType): mixed;

    /**
     * @return TReturn
     */
    public function conditional(Type $type, Argument|Type $subject, Type $if, Type $then, Type $else): mixed;

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
     * @return TReturn
     */
    public function intMask(Type $type, Type $innerType): mixed;

    /**
     * @param Type<int> $type
     * @return TReturn
     */
    public function intRange(Type $type, ?int $min, ?int $max): mixed;

    /**
     * @param Type<iterable<mixed>> $type
     * @return TReturn
     */
    public function iterable(Type $type, Type $keyType, Type $valueType): mixed;

    /**
     * @return TReturn
     */
    public function key(Type $type, Type $innerType): mixed;

    /**
     * @param Type<list<mixed>> $type
     * @return TReturn
     */
    public function list(Type $type, Type $valueType): mixed;

    /**
     * @return TReturn
     */
    public function literal(Type $type, bool|int|float|string $value): mixed;

    /**
     * @return TReturn
     */
    public function mixed(Type $type): mixed;

    /**
     * @param Type<non-empty-string> $type
     * @return TReturn
     */
    public function namedClassString(Type $type, Type $objectType): mixed;

    /**
     * @param Type<object> $type
     * @param non-empty-string $class
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
     * @param non-empty-list<Type> $types
     * @return TReturn
     */
    public function union(Type $type, array $types): mixed;

    /**
     * @return TReturn
     */
    public function value(Type $type, Type $innerType): mixed;

    /**
     * @return TReturn
     */
    public function varianceAware(Type $type, Type $innerType, Variance $variance): mixed;

    /**
     * @param Type<void> $type
     * @return TReturn
     */
    public function void(Type $type): mixed;
}
