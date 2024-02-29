<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Type\Argument;
use Typhoon\Type\ArrayElement;
use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;
use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Parameter;
use Typhoon\Type\Property;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Variance;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @extends DefaultTypeVisitor<Type>
 */
abstract class RecursiveTypeReplacer extends DefaultTypeVisitor
{
    public function alias(Type $self, string $class, string $name, array $arguments): mixed
    {
        return types::alias($class, $name, ...array_map(
            fn(Type $templateArgument): Type => $templateArgument->accept($this),
            $arguments,
        ));
    }

    public function classConstant(Type $self, Type $class, string $name): mixed
    {
        return types::classConstant($class->accept($this), $name);
    }

    public function intMask(Type $self, Type $type): mixed
    {
        return types::intMask($type->accept($this));
    }

    public function literal(Type $self, Type $type): mixed
    {
        return types::literal($type->accept($this));
    }

    public function list(Type $self, Type $value): mixed
    {
        return types::list($value->accept($this));
    }

    public function arrayShape(Type $self, array $elements, bool $sealed): mixed
    {
        return types::arrayShape(
            array_map(
                fn(ArrayElement $element): ArrayElement => types::arrayElement(
                    $element->type->accept($this),
                    $element->optional,
                ),
                $elements,
            ),
            $sealed,
        );
    }

    public function array(Type $self, Type $key, Type $value): mixed
    {
        return types::array($key->accept($this), $value->accept($this));
    }

    public function iterable(Type $self, Type $key, Type $value): mixed
    {
        return types::iterable($key->accept($this), $value->accept($this));
    }

    public function namedObject(Type $self, string $class, array $arguments): mixed
    {
        return types::object($class, ...array_map(
            fn(Type $templateArgument): Type => $templateArgument->accept($this),
            $arguments,
        ));
    }

    public function template(Type $self, string $name, AtClass|AtFunction|AtMethod $declaredAt, array $arguments): mixed
    {
        return types::template($name, $declaredAt, ...array_map(
            fn(Type $templateArgument): Type => $templateArgument->accept($this),
            $arguments,
        ));
    }

    public function varianceAware(Type $self, Type $type, Variance $variance): mixed
    {
        return types::varianceAware($type->accept($this), $variance);
    }

    public function objectShape(Type $self, array $properties): mixed
    {
        return types::objectShape(
            array_map(
                fn(Property $property): Property => types::prop(
                    $property->type->accept($this),
                    $property->optional,
                ),
                $properties,
            ),
        );
    }

    public function closure(Type $self, array $parameters, Type $return): mixed
    {
        return types::closure(
            array_map(
                fn(Parameter $parameter): Parameter => types::param(
                    type: $parameter->type->accept($this),
                    hasDefault: $parameter->hasDefault,
                    variadic: $parameter->variadic,
                    byReference: $parameter->byReference,
                    name: $parameter->name,
                ),
                $parameters,
            ),
            $return->accept($this),
        );
    }

    public function callable(Type $self, array $parameters, Type $return): mixed
    {
        return types::callable(
            array_map(
                fn(Parameter $parameter): Parameter => types::param(
                    type: $parameter->type->accept($this),
                    hasDefault: $parameter->hasDefault,
                    variadic: $parameter->variadic,
                    byReference: $parameter->byReference,
                    name: $parameter->name,
                ),
                $parameters,
            ),
            $return->accept($this),
        );
    }

    public function key(Type $self, Type $type): mixed
    {
        return types::key($type->accept($this));
    }

    public function value(Type $self, Type $type): mixed
    {
        return types::value($type->accept($this));
    }

    public function offset(Type $self, Type $type, Type $offset): mixed
    {
        return types::offset($type->accept($this), $offset->accept($this));
    }

    public function conditional(Type $self, Argument|Type $subject, Type $if, Type $then, Type $else): mixed
    {
        return types::conditional($subject, $if->accept($this), $then->accept($this), $else->accept($this));
    }

    public function intersection(Type $self, array $types): mixed
    {
        return types::intersection(...array_map(fn(Type $part): Type => $part->accept($this), $types));
    }

    public function union(Type $self, array $types): mixed
    {
        return types::union(...array_map(fn(Type $part): Type => $part->accept($this), $types));
    }

    public function nonEmpty(Type $self, Type $type): mixed
    {
        return types::nonEmpty($type->accept($this));
    }

    protected function default(Type $self): mixed
    {
        return $self;
    }
}
