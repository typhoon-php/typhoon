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
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function alias(Type $self, string $class, string $name, array $arguments): mixed;

    /**
     * @param Type<array<mixed>> $self
     * @param array<ArrayElement> $elements
     * @return TReturn
     */
    public function array(Type $self, Type $key, Type $value, array $elements): mixed;

    /**
     * @param Type<bool> $self
     * @return TReturn
     */
    public function bool(Type $self): mixed;

    /**
     * @param Type<callable> $self
     * @param list<Parameter> $parameters
     * @return TReturn
     */
    public function callable(Type $self, array $parameters, Type $return): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function classConstant(Type $self, Type $class, string $name): mixed;

    /**
     * @param Type<non-empty-string> $self
     * @return TReturn
     */
    public function classString(Type $self, Type $class): mixed;

    /**
     * @param Type<non-empty-string> $self
     * @param non-empty-string $class
     * @return TReturn
     */
    public function classStringLiteral(Type $self, string $class): mixed;

    /**
     * @param Type<\Closure> $self
     * @param list<Parameter> $parameters
     * @return TReturn
     */
    public function closure(Type $self, array $parameters, Type $return): mixed;

    /**
     * @return TReturn
     */
    public function conditional(Type $self, Argument|Type $subject, Type $if, Type $then, Type $else): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function constant(Type $self, string $name): mixed;

    /**
     * @param Type<float> $self
     * @return TReturn
     */
    public function float(Type $self): mixed;

    /**
     * @param Type<int> $self
     * @return TReturn
     */
    public function int(Type $self): mixed;

    /**
     * @param non-empty-list<Type> $types
     * @return TReturn
     */
    public function intersection(Type $self, array $types): mixed;

    /**
     * @param Type<int> $self
     * @return TReturn
     */
    public function intMask(Type $self, Type $type): mixed;

    /**
     * @param Type<int> $self
     * @return TReturn
     */
    public function intRange(Type $self, ?int $min, ?int $max): mixed;

    /**
     * @param Type<iterable<mixed>> $self
     * @return TReturn
     */
    public function iterable(Type $self, Type $key, Type $value): mixed;

    /**
     * @return TReturn
     */
    public function key(Type $self, Type $type): mixed;

    /**
     * @param Type<list<mixed>> $self
     * @param array<int, ArrayElement> $elements
     * @return TReturn
     */
    public function list(Type $self, Type $value, array $elements): mixed;

    /**
     * @return TReturn
     */
    public function literal(Type $self, Type $type): mixed;

    /**
     * @return TReturn
     */
    public function literalValue(Type $self, bool|int|float|string $value): mixed;

    /**
     * @return TReturn
     */
    public function mixed(Type $self): mixed;

    /**
     * @param Type<object> $self
     * @param non-empty-string $class
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function namedObject(Type $self, string $class, array $arguments): mixed;

    /**
     * @param Type<never> $self
     * @return TReturn
     */
    public function never(Type $self): mixed;

    /**
     * @return TReturn
     */
    public function nonEmpty(Type $self, Type $type): mixed;

    /**
     * @param Type<null> $self
     * @return TReturn
     */
    public function null(Type $self): mixed;

    /**
     * @param Type<numeric-string> $self
     * @return TReturn
     */
    public function numericString(Type $self): mixed;

    /**
     * @param Type<object> $self
     * @return TReturn
     */
    public function object(Type $self): mixed;

    /**
     * @param Type<object> $self
     * @param non-empty-array<string, Property> $properties
     * @return TReturn
     */
    public function objectShape(Type $self, array $properties): mixed;

    /**
     * @return TReturn
     */
    public function offset(Type $self, Type $type, Type $offset): mixed;

    /**
     * @param Type<resource> $self
     * @return TReturn
     */
    public function resource(Type $self): mixed;

    /**
     * @param Type<string> $self
     * @return TReturn
     */
    public function string(Type $self): mixed;

    /**
     * @param non-empty-string $name
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function template(Type $self, string $name, AtFunction|AtClass|AtMethod $declaredAt, array $arguments): mixed;

    /**
     * @param Type<truthy-string> $self
     * @return TReturn
     */
    public function truthyString(Type $self): mixed;

    /**
     * @param non-empty-list<Type> $types
     * @return TReturn
     */
    public function union(Type $self, array $types): mixed;

    /**
     * @deprecated will be removed in 0.4.0
     * @return TReturn
     */
    public function value(Type $self, Type $type): mixed;

    /**
     * @return TReturn
     */
    public function varianceAware(Type $self, Type $type, Variance $variance): mixed;

    /**
     * @param Type<void> $self
     * @return TReturn
     */
    public function void(Type $self): mixed;
}
