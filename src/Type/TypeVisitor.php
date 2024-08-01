<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\TemplateId;

/**
 * @api
 * @template-covariant TReturn
 */
interface TypeVisitor
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
    public function int(Type $type, Type $minType, Type $maxType): mixed;

    /**
     * @param Type<int> $type
     * @return TReturn
     */
    public function intValue(Type $type, int $value): mixed;

    /**
     * @param Type<positive-int> $type
     * @return TReturn
     */
    public function intMask(Type $type, Type $ofType): mixed;

    /**
     * @param Type<float> $type
     * @return TReturn
     */
    public function float(Type $type, Type $minType, Type $maxType): mixed;

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
    public function classString(Type $type, Type $classType): mixed;

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
     * @return TReturn
     */
    public function key(Type $type, Type $arrayType): mixed;

    /**
     * @return TReturn
     */
    public function offset(Type $type, Type $arrayType, Type $keyType): mixed;

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
    public function namedObject(Type $type, NamedClassId $classId, array $typeArguments): mixed;

    /**
     * @param Type<object> $type
     * @param list<Type> $typeArguments
     * @return TReturn
     */
    public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed;

    /**
     * @param Type<object> $type
     * @param list<Type> $typeArguments
     * @return TReturn
     */
    public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClassId): mixed;

    /**
     * @param Type<object> $type
     * @param list<Type> $typeArguments
     * @return TReturn
     */
    public function static(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed;

    /**
     * @param Type<callable> $type
     * @param list<Parameter> $parameters
     * @return TReturn
     */
    public function callable(Type $type, array $parameters, Type $returnType): mixed;

    /**
     * @return TReturn
     */
    public function constant(Type $type, ConstantId $constantId): mixed;

    /**
     * @param non-empty-string $name
     * @return TReturn
     */
    public function classConstant(Type $type, Type $classType, string $name): mixed;

    /**
     * @return TReturn
     */
    public function classConstantMask(Type $type, Type $classType, string $namePrefix): mixed;

    /**
     * @param list<Type> $typeArguments
     * @return TReturn
     */
    public function alias(Type $type, AliasId $aliasId, array $typeArguments): mixed;

    /**
     * @return TReturn
     */
    public function template(Type $type, TemplateId $templateId): mixed;

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
     * @return TReturn
     */
    public function argument(Type $type, ParameterId $parameterId): mixed;

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
