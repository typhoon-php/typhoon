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
    public function alias(Type $type, string $class, string $name): mixed
    {
        return $this->default($type);
    }

    public function anyLiteral(Type $type, Type $innerType): mixed
    {
        return $this->default($type);
    }

    public function array(Type $type, Type $keyType, Type $valueType): mixed
    {
        return $this->default($type);
    }

    public function arrayShape(Type $type, array $elements, bool $sealed): mixed
    {
        return $this->default($type);
    }

    public function bool(Type $type): mixed
    {
        return $this->default($type);
    }

    public function callable(Type $type, array $parameters, ?Type $returnType): mixed
    {
        return $this->default($type);
    }

    public function classConstant(Type $type, string $class, string $name): mixed
    {
        return $this->default($type);
    }

    public function classString(Type $type): mixed
    {
        return $this->default($type);
    }

    public function classStringLiteral(Type $type, string $class): mixed
    {
        return $this->default($type);
    }

    public function closure(Type $type, array $parameters, ?Type $returnType): mixed
    {
        return $this->default($type);
    }

    public function conditional(Type $type, TemplateType|Argument $subject, Type $if, Type $then, Type $else): mixed
    {
        return $this->default($type);
    }

    public function constant(Type $type, string $name): mixed
    {
        return $this->default($type);
    }

    public function float(Type $type): mixed
    {
        return $this->default($type);
    }

    public function int(Type $type): mixed
    {
        return $this->default($type);
    }

    public function intersection(Type $type, array $types): mixed
    {
        return $this->default($type);
    }

    public function intMask(Type $type, array $ints): mixed
    {
        return $this->default($type);
    }

    public function intMaskOf(Type $type, Type $innerType): mixed
    {
        return $this->default($type);
    }

    public function intRange(Type $type, ?int $min, ?int $max): mixed
    {
        return $this->default($type);
    }

    public function iterable(Type $type, Type $keyType, Type $valueType): mixed
    {
        return $this->default($type);
    }

    public function keyOf(Type $type, Type $innerType): mixed
    {
        return $this->default($type);
    }

    public function list(Type $type, Type $valueType): mixed
    {
        return $this->default($type);
    }

    public function literal(Type $type, float|bool|int|string $value): mixed
    {
        return $this->default($type);
    }

    public function mixed(Type $type): mixed
    {
        return $this->default($type);
    }

    public function namedClassString(Type $type, Type $objectType): mixed
    {
        return $this->default($type);
    }

    public function namedObject(Type $type, string $class, array $templateArguments): mixed
    {
        return $this->default($type);
    }

    public function never(Type $type): mixed
    {
        return $this->default($type);
    }

    public function nonEmpty(Type $type, Type $innerType): mixed
    {
        return $this->default($type);
    }

    public function null(Type $type): mixed
    {
        return $this->default($type);
    }

    public function numericString(Type $type): mixed
    {
        return $this->default($type);
    }

    public function object(Type $type): mixed
    {
        return $this->default($type);
    }

    public function objectShape(Type $type, array $properties): mixed
    {
        return $this->default($type);
    }

    public function offset(Type $type, Type $innerType, Type $offset): mixed
    {
        return $this->default($type);
    }

    public function resource(Type $type): mixed
    {
        return $this->default($type);
    }

    public function string(Type $type): mixed
    {
        return $this->default($type);
    }

    public function template(Type $type, string $name, AtClass|AtFunction|AtMethod $declaredAt, Type $constraint): mixed
    {
        return $this->default($type);
    }

    public function truthyString(Type $type): mixed
    {
        return $this->default($type);
    }

    public function union(Type $type, array $types): mixed
    {
        return $this->default($type);
    }

    public function valueOf(Type $type, Type $innerType): mixed
    {
        return $this->default($type);
    }

    public function varianceAware(Type $type, Type $innerType, Variance $variance): mixed
    {
        return $this->default($type);
    }

    public function void(Type $type): mixed
    {
        return $this->default($type);
    }

    /**
     * @return TReturn
     */
    abstract protected function default(Type $type): mixed;
}
