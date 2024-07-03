<?php

declare(strict_types=1);

namespace Typhoon\Type\Visitor;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\Type\Argument;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;
use Typhoon\Type\Variance;

/**
 * @api
 * @template-covariant TReturn
 * @implements TypeVisitor<TReturn>
 */
abstract class DefaultTypeVisitor implements TypeVisitor
{
    public function never(Type $self): mixed
    {
        return $this->default($self);
    }

    public function void(Type $self): mixed
    {
        return $this->default($self);
    }

    public function null(Type $self): mixed
    {
        return $this->default($self);
    }

    public function true(Type $self): mixed
    {
        return $this->default($self);
    }

    public function false(Type $self): mixed
    {
        return $this->default($self);
    }

    public function int(Type $self, ?int $min, ?int $max): mixed
    {
        return $this->default($self);
    }

    public function intMask(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function float(Type $self): mixed
    {
        return $this->default($self);
    }

    public function floatValue(Type $self, float $value): mixed
    {
        return $this->default($self);
    }

    public function string(Type $self): mixed
    {
        return $this->default($self);
    }

    public function stringValue(Type $self, string $value): mixed
    {
        return $this->default($self);
    }

    public function classString(Type $self, Type $class): mixed
    {
        return $this->default($self);
    }

    public function resource(Type $self): mixed
    {
        return $this->default($self);
    }

    public function list(Type $self, Type $value, array $elements): mixed
    {
        return $this->default($self);
    }

    public function array(Type $self, Type $key, Type $value, array $elements): mixed
    {
        return $this->default($self);
    }

    public function key(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function offset(Type $self, Type $type, Type $offset): mixed
    {
        return $this->default($self);
    }

    public function iterable(Type $self, Type $key, Type $value): mixed
    {
        return $this->default($self);
    }

    public function object(Type $self, array $properties): mixed
    {
        return $this->default($self);
    }

    public function namedObject(Type $self, ClassId $class, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function self(Type $self, ?ClassId $resolvedClass, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function parent(Type $self, ?NamedClassId $resolvedClass, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function static(Type $self, ?ClassId $resolvedClass, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function callable(Type $self, array $parameters, Type $return): mixed
    {
        return $this->default($self);
    }

    public function union(Type $self, array $types): mixed
    {
        return $this->default($self);
    }

    public function intersection(Type $self, array $types): mixed
    {
        return $this->default($self);
    }

    public function mixed(Type $self): mixed
    {
        return $this->default($self);
    }

    public function numeric(Type $self): mixed
    {
        return $this->default($self);
    }

    public function truthy(Type $self): mixed
    {
        return $this->default($self);
    }

    public function literal(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function nonEmpty(Type $self, Type $type): mixed
    {
        return $this->default($self);
    }

    public function template(Type $self, TemplateId $template): mixed
    {
        return $this->default($self);
    }

    public function varianceAware(Type $self, Type $type, Variance $variance): mixed
    {
        return $this->default($self);
    }

    public function constant(Type $self, ConstantId $constant): mixed
    {
        return $this->default($self);
    }

    public function classConstant(Type $self, Type $class, string $name): mixed
    {
        return $this->default($self);
    }

    public function alias(Type $self, AliasId $alias, array $arguments): mixed
    {
        return $this->default($self);
    }

    public function conditional(Type $self, Argument|Type $subject, Type $if, Type $then, Type $else): mixed
    {
        return $this->default($self);
    }

    /**
     * @return TReturn
     */
    abstract protected function default(Type $self): mixed;
}
