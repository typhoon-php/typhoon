<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Parameter;
use Typhoon\Type\ShapeElement;
use Typhoon\Type\Type;
use Typhoon\Type\Variance;

/**
 * @api
 * @template-covariant TReturn
 */
interface ResolvedTypeVisitor
{
    /**
     * @param Type<never> $type
     * @return TReturn
     */
    public function never(Type $type): mixed;

    /**
     * @param Type<void> $type
     * @return TReturn
     */
    public function void(Type $type): mixed;

    /**
     * @param Type<null> $type
     * @return TReturn
     */
    public function null(Type $type): mixed;

    /**
     * @param Type<true> $type
     * @return TReturn
     */
    public function true(Type $type): mixed;

    /**
     * @param Type<false> $type
     * @return TReturn
     */
    public function false(Type $type): mixed;

    /**
     * @param Type<int> $type
     * @return TReturn
     */
    public function int(Type $type, int $min, int $max): mixed;

    /**
     * @param Type<int> $type
     * @return TReturn
     */
    public function intValue(Type $type, int $value): mixed;

    /**
     * @param Type<float> $type
     * @return TReturn
     */
    public function float(Type $type, int|float $min, int|float $max): mixed;

    /**
     * @param Type<float> $type
     * @return TReturn
     */
    public function floatValue(Type $type, float $value): mixed;

    /**
     * @param Type<string> $type
     * @return TReturn
     */
    public function string(Type $type): mixed;

    /**
     * @param Type<string> $type
     * @return TReturn
     */
    public function stringValue(Type $type, string $value): mixed;

    /**
     * @param Type<non-empty-string> $type
     * @return TReturn
     */
    public function classString(Type $type, NamedClassId|AnonymousClassId $classId): mixed;

    /**
     * @param Type<numeric> $type
     * @return TReturn
     */
    public function numeric(Type $type): mixed;

    /**
     * @return TReturn
     */
    public function literal(Type $type, Type $ofType): mixed;

    /**
     * @param Type<resource> $type
     * @return TReturn
     */
    public function resource(Type $type): mixed;

    /**
     * @param Type<list<mixed>> $type
     * @param array<non-negative-int, ShapeElement> $elements
     * @return TReturn
     */
    public function list(Type $type, Type $valueType, array $elements): mixed;

    /**
     * @param Type<array<mixed>> $type
     * @param array<ShapeElement> $elements
     * @return TReturn
     */
    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed;

    /**
     * @param Type<iterable<mixed>> $type
     * @return TReturn
     */
    public function iterable(Type $type, Type $keyType, Type $valueType): mixed;

    /**
     * @param Type<object> $type
     * @param array<string, ShapeElement> $properties
     * @return TReturn
     */
    public function object(Type $type, array $properties): mixed;

    /**
     * @param Type<object> $type
     * @param list<Type> $typeArguments
     * @return TReturn
     */
    public function namedObject(Type $type, NamedClassId|AnonymousClassId $classId, array $typeArguments): mixed;

    /**
     * @param Type<callable> $type
     * @param list<Parameter> $parameters
     * @return TReturn
     */
    public function callable(Type $type, array $parameters, Type $returnType): mixed;

    /**
     * @return TReturn
     */
    public function varianceAware(Type $type, Type $ofType, Variance $variance): mixed;

    /**
     * @param non-empty-list<Type> $ofTypes
     * @return TReturn
     */
    public function union(Type $type, array $ofTypes): mixed;

    /**
     * @return TReturn
     */
    public function conditional(Type $type, Type $subjectType, Type $ifType, Type $thenType, Type $elseType): mixed;

    /**
     * @param non-empty-list<Type> $ofTypes
     * @return TReturn
     */
    public function intersection(Type $type, array $ofTypes): mixed;

    /**
     * @return TReturn
     */
    public function not(Type $type, Type $ofType): mixed;

    /**
     * @return TReturn
     */
    public function mixed(Type $type): mixed;
}
