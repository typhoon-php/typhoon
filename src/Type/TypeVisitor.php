<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\TemplateId;

/**
 * @api
 * @template-covariant TReturn
 * @todo rename $self, $type
 */
interface TypeVisitor
{
    /**
     * @param Type<never> $self
     * @return TReturn
     */
    public function never(Type $self): mixed;

    /**
     * @param Type<void> $self
     * @return TReturn
     */
    public function void(Type $self): mixed;

    /**
     * @param Type<null> $self
     * @return TReturn
     */
    public function null(Type $self): mixed;

    /**
     * @return TReturn
     */
    public function true(Type $self): mixed;

    /**
     * @return TReturn
     */
    public function false(Type $self): mixed;

    /**
     * @param Type<int> $self
     * @return TReturn
     */
    public function int(Type $self, ?int $min, ?int $max): mixed;

    /**
     * @param Type<int> $self
     * @return TReturn
     */
    public function intMask(Type $self, Type $type): mixed;

    /**
     * @param Type<float> $self
     * @return TReturn
     */
    public function float(Type $self): mixed;

    /**
     * @return TReturn
     */
    public function floatValue(Type $self, float $value): mixed;

    /**
     * @param Type<string> $self
     * @return TReturn
     */
    public function string(Type $self): mixed;

    /**
     * @param Type<string> $self
     * @return TReturn
     */
    public function stringValue(Type $self, string $value): mixed;

    /**
     * @param Type<lowercase-string> $self
     * @return TReturn
     */
    public function lowercaseString(Type $self): mixed;

    /**
     * @param Type<non-empty-string> $self
     * @return TReturn
     */
    public function classString(Type $self, Type $class): mixed;

    /**
     * @param Type<numeric> $self
     * @return TReturn
     */
    public function numeric(Type $self): mixed;

    /**
     * @param Type<resource> $self
     * @return TReturn
     */
    public function resource(Type $self): mixed;

    /**
     * @param Type<list<mixed>> $self
     * @param array<non-negative-int, ArrayElement> $elements
     * @return TReturn
     */
    public function list(Type $self, Type $value, array $elements): mixed;

    /**
     * @param Type<array<mixed>> $self
     * @param array<ArrayElement> $elements
     * @return TReturn
     */
    public function array(Type $self, Type $key, Type $value, array $elements): mixed;

    /**
     * @return TReturn
     */
    public function key(Type $self, Type $type): mixed;

    /**
     * @return TReturn
     */
    public function offset(Type $self, Type $type, Type $offset): mixed;

    /**
     * @param Type<iterable<mixed>> $self
     * @return TReturn
     */
    public function iterable(Type $self, Type $key, Type $value): mixed;

    /**
     * @param Type<object> $self
     * @param array<string, Property> $properties
     * @return TReturn
     */
    public function object(Type $self, array $properties): mixed;

    /**
     * @param Type<object> $self
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function namedObject(Type $self, ClassId $class, array $arguments): mixed;

    /**
     * @param Type<object> $self
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function self(Type $self, ?ClassId $resolvedClass, array $arguments): mixed;

    /**
     * @param Type<object> $self
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function parent(Type $self, ?NamedClassId $resolvedClass, array $arguments): mixed;

    /**
     * @param Type<object> $self
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function static(Type $self, ?ClassId $resolvedClass, array $arguments): mixed;

    /**
     * @param Type<callable> $self
     * @param list<Parameter> $parameters
     * @return TReturn
     */
    public function callable(Type $self, array $parameters, Type $return): mixed;

    /**
     * @param non-empty-list<Type> $types
     * @return TReturn
     */
    public function union(Type $self, array $types): mixed;

    /**
     * @param non-empty-list<Type> $types
     * @return TReturn
     */
    public function intersection(Type $self, array $types): mixed;

    /**
     * @return TReturn
     */
    public function mixed(Type $self): mixed;

    /**
     * @return TReturn
     */
    public function not(Type $self, Type $type): mixed;

    /**
     * @return TReturn
     */
    public function literal(Type $self, Type $type): mixed;

    /**
     * @return TReturn
     */
    public function template(Type $self, TemplateId $template): mixed;

    /**
     * @return TReturn
     */
    public function varianceAware(Type $self, Type $type, Variance $variance): mixed;

    /**
     * @return TReturn
     */
    public function constant(Type $self, ConstantId $constant): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function classConstant(Type $self, Type $class, string $name): mixed;

    /**
     * @param list<Type> $arguments
     * @return TReturn
     */
    public function alias(Type $self, AliasId $alias, array $arguments): mixed;

    /**
     * @return TReturn
     */
    public function conditional(Type $self, Argument|Type $subject, Type $if, Type $then, Type $else): mixed;
}
