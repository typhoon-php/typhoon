<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TReturn
 * @implements TypeVisitor<TReturn>
 */
abstract class DefaultTypeVisitor implements TypeVisitor
{
    public function alias(Type $self, string $class, string $name, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function array(Type $self, Type $key, Type $value): mixed
    {
        return $this->default($self);
    }

    public function arrayShape(Type $self, array $elements, bool $sealed): mixed
    {
        return $this->default($self);
    }

    public function bool(Type $self): mixed
    {
        return $this->default($self);
    }

    public function callable(Type $self, array $parameters, Type $return): mixed
    {
        return $this->default($self);
    }

    public function classConstant(Type $self, Type $class, string $name): mixed
    {
        return $this->default($self);
    }

    public function classString(Type $self): mixed
    {
        return $this->default($self);
    }

    public function classStringLiteral(Type $self, string $class): mixed
    {
        return $this->default($self);
    }

    public function closure(Type $self, array $parameters, Type $return): mixed
    {
        return $this->default($self);
    }

    public function conditional(Type $self, Argument|Type $subject, Type $if, Type $then, Type $else): mixed
    {
        return $this->default($self);
    }

    public function constant(Type $self, string $name): mixed
    {
        return $this->default($self);
    }

    public function float(Type $self): mixed
    {
        return $this->default($self);
    }

    public function int(Type $self): mixed
    {
        return $this->default($self);
    }

    public function intersection(Type $self, array $types): mixed
    {
        return $this->default($self);
    }

    public function intMask(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function intRange(Type $self, ?int $min, ?int $max): mixed
    {
        return $this->default($self);
    }

    public function iterable(Type $self, Type $key, Type $value): mixed
    {
        return $this->default($self);
    }

    public function key(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function list(Type $self, Type $value): mixed
    {
        return $this->default($self);
    }

    public function literal(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return $this->default($self);
    }

    public function mixed(Type $self): mixed
    {
        return $this->default($self);
    }

    public function namedClassString(Type $self, Type $object): mixed
    {
        return $this->default($self);
    }

    public function namedObject(Type $self, string $class, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function never(Type $self): mixed
    {
        return $this->default($self);
    }

    public function nonEmpty(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function null(Type $self): mixed
    {
        return $this->default($self);
    }

    public function numericString(Type $self): mixed
    {
        return $this->default($self);
    }

    public function object(Type $self): mixed
    {
        return $this->default($self);
    }

    public function objectShape(Type $self, array $properties): mixed
    {
        return $this->default($self);
    }

    public function offset(Type $self, Type $type, Type $offset): mixed
    {
        return $this->default($self);
    }

    public function resource(Type $self): mixed
    {
        return $this->default($self);
    }

    public function string(Type $self): mixed
    {
        return $this->default($self);
    }

    public function template(Type $self, string $name, AtClass|AtFunction|AtMethod $declaredAt, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function truthyString(Type $self): mixed
    {
        return $this->default($self);
    }

    public function union(Type $self, array $types): mixed
    {
        return $this->default($self);
    }

    public function value(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function varianceAware(Type $self, Type $type, Variance $variance): mixed
    {
        return $this->default($self);
    }

    public function void(Type $self): mixed
    {
        return $this->default($self);
    }

    /**
     * @return TReturn
     */
    abstract protected function default(Type $self): mixed;
}
