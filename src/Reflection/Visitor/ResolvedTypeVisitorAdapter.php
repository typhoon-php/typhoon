<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Parameter;
use Typhoon\Type\ShapeElement;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;
use Typhoon\Type\Variance;

/**
 * @api
 * @template-covariant TReturn
 * @implements TypeVisitor<TReturn>
 */
final class ResolvedTypeVisitorAdapter implements TypeVisitor
{
    private readonly ExternalTypeResolver $typeResolver;

    /**
     * @param ResolvedTypeVisitor<TReturn> $resolvedTypeVisitor
     */
    public function __construct(
        TyphoonReflector $reflector,
        private ResolvedTypeVisitor $resolvedTypeVisitor,
    ) {
        $this->typeResolver = new ExternalTypeResolver($reflector);
    }

    public function never(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->never($type);
    }

    public function void(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->void($type);
    }

    public function null(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->null($type);
    }

    public function true(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->true($type);
    }

    public function false(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->false($type);
    }

    public function int(Type $type, Type $minType, Type $maxType): mixed
    {
        $min = $minType->accept($this->typeResolver)->accept(new IntLimitResolver());
        $max = $maxType->accept($this->typeResolver)->accept(new IntLimitResolver());

        if ($min === $max) {
            return $this->resolvedTypeVisitor->intValue($type, $min);
        }

        return $this->resolvedTypeVisitor->int($type, min: $min, max: $max);
    }

    public function intValue(Type $type, int $value): mixed
    {
        return $this->resolvedTypeVisitor->intValue($type, $value);
    }

    public function intMask(Type $type, Type $ofType): mixed
    {
        return $this->resolvedTypeVisitor->intValue(
            type: $type,
            value: $ofType->accept($this->typeResolver)->accept(new IntMaskResolver()),
        );
    }

    public function float(Type $type, Type $minType, Type $maxType): mixed
    {
        $min = $minType->accept($this->typeResolver)->accept(new FloatLimitResolver());
        $max = $maxType->accept($this->typeResolver)->accept(new FloatLimitResolver());

        if ($min === $max) {
            if (\is_int($min)) {
                /** @psalm-suppress InvalidArgument */
                return $this->resolvedTypeVisitor->intValue($type, $min);
            }

            return $this->resolvedTypeVisitor->floatValue($type, $min);
        }

        return $this->resolvedTypeVisitor->float($type, min: $min, max: $max);
    }

    public function floatValue(Type $type, float $value): mixed
    {
        return $this->resolvedTypeVisitor->floatValue($type, $value);
    }

    public function string(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->string($type);
    }

    public function stringValue(Type $type, string $value): mixed
    {
        return $this->resolvedTypeVisitor->stringValue($type, $value);
    }

    public function classString(Type $type, Type $classType): mixed
    {
        $classId = $classType->accept($this->typeResolver)->accept(new ClassIdResolver());

        return $this->resolvedTypeVisitor->classString($type, $classId);
    }

    public function numeric(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->numeric($type);
    }

    public function literal(Type $type, Type $ofType): mixed
    {
        return $this->resolvedTypeVisitor->literal($type, $ofType->accept($this->typeResolver));
    }

    public function resource(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->resource($type);
    }

    public function list(Type $type, Type $valueType, array $elements): mixed
    {
        return $this->resolvedTypeVisitor->list(
            type: $type,
            valueType: $valueType->accept($this->typeResolver),
            elements: array_map(
                fn(ShapeElement $element): ShapeElement => new ShapeElement(
                    type: $element->type->accept($this->typeResolver),
                    optional: $element->optional,
                ),
                $elements,
            ),
        );
    }

    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
    {
        return $this->resolvedTypeVisitor->array(
            type: $type,
            keyType: $keyType->accept($this->typeResolver),
            valueType: $valueType->accept($this->typeResolver),
            elements: array_map(
                fn(ShapeElement $element): ShapeElement => new ShapeElement(
                    type: $element->type->accept($this->typeResolver),
                    optional: $element->optional,
                ),
                $elements,
            ),
        );
    }

    public function key(Type $type, Type $arrayType): mixed
    {
        // todo
        throw new \LogicException('Not supported yet');
    }

    public function offset(Type $type, Type $arrayType, Type $keyType): mixed
    {
        // todo
        throw new \LogicException('Not supported yet');
    }

    public function iterable(Type $type, Type $keyType, Type $valueType): mixed
    {
        return $this->resolvedTypeVisitor->iterable(
            type: $type,
            keyType: $keyType->accept($this->typeResolver),
            valueType: $valueType->accept($this->typeResolver),
        );
    }

    public function object(Type $type, array $properties): mixed
    {
        return $this->resolvedTypeVisitor->object(
            type: $type,
            properties: array_map(
                fn(ShapeElement $property): ShapeElement => new ShapeElement(
                    type: $property->type->accept($this->typeResolver),
                    optional: $property->optional,
                ),
                $properties,
            ),
        );
    }

    public function namedObject(Type $type, NamedClassId|AnonymousClassId $classId, array $typeArguments): mixed
    {
        return $this->resolvedTypeVisitor->namedObject(
            type: $type,
            classId: $classId,
            typeArguments: array_map(
                fn(Type $type): Type => $type->accept($this->typeResolver),
                $typeArguments,
            ),
        );
    }

    public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        // todo: pass self via constructor
        return $this->namedObject($type, $resolvedClassId ?? throw new \LogicException(), $typeArguments);
    }

    public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClassId): mixed
    {
        // todo: pass self via constructor
        return $this->namedObject($type, $resolvedClassId ?? throw new \LogicException(), $typeArguments);
    }

    public function static(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        // todo: pass self via constructor
        return $this->namedObject($type, $resolvedClassId ?? throw new \LogicException(), $typeArguments);
    }

    public function callable(Type $type, array $parameters, Type $returnType): mixed
    {
        return $this->resolvedTypeVisitor->callable(
            type: $type,
            parameters: array_map(
                fn(Parameter $parameter): Parameter => new Parameter(
                    type: $parameter->type->accept($this->typeResolver),
                    hasDefault: $parameter->hasDefault,
                    variadic: $parameter->variadic,
                    byReference: $parameter->byReference,
                ),
                $parameters,
            ),
            returnType: $returnType->accept($this->typeResolver),
        );
    }

    public function constant(Type $type, ConstantId $constantId): mixed
    {
        // TODO refactor when constant reflection is ready

        $value = \constant($constantId->name);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return match (true) {
            $value === null => $this->resolvedTypeVisitor->null($type),
            $value === true => $this->resolvedTypeVisitor->true($type),
            $value === false => $this->resolvedTypeVisitor->false($type),
            \is_int($value) => $this->resolvedTypeVisitor->intValue($type, $value),
            \is_float($value) => $this->resolvedTypeVisitor->floatValue($type, $value),
            \is_string($value) => $this->resolvedTypeVisitor->stringValue($type, $value),
            \is_resource($value) => $this->resolvedTypeVisitor->resource($type),
            \is_object($value) => $this->resolvedTypeVisitor->namedObject($type, Id::class($value::class), []),
            default => throw new \LogicException(),
        };
    }

    public function classConstant(Type $type, Type $classType, string $name): mixed
    {
        return $type->accept($this->typeResolver)->accept($this);
    }

    public function classConstantMask(Type $type, Type $classType, string $namePrefix): mixed
    {
        return $type->accept($this->typeResolver)->accept($this);
    }

    public function alias(Type $type, AliasId $aliasId, array $typeArguments): mixed
    {
        return $type->accept($this->typeResolver)->accept($this);
    }

    public function template(Type $type, TemplateId $templateId): mixed
    {
        // todo
        throw new \LogicException('Not supported yet');
    }

    public function varianceAware(Type $type, Type $ofType, Variance $variance): mixed
    {
        return $this->resolvedTypeVisitor->varianceAware($type, $ofType->accept($this->typeResolver), $variance);
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        return $this->resolvedTypeVisitor->union($type, array_map(
            fn(Type $type): Type => $type->accept($this->typeResolver),
            $ofTypes,
        ));
    }

    public function conditional(Type $type, Type $subjectType, Type $ifType, Type $thenType, Type $elseType): mixed
    {
        return $this->resolvedTypeVisitor->conditional(
            type: $type,
            subjectType: $subjectType->accept($this->typeResolver),
            ifType: $ifType->accept($this->typeResolver),
            thenType: $thenType->accept($this->typeResolver),
            elseType: $elseType->accept($this->typeResolver),
        );
    }

    public function argument(Type $type, ParameterId $parameterId): mixed
    {
        // todo
        throw new \LogicException('Not supported yet');
    }

    public function intersection(Type $type, array $ofTypes): mixed
    {
        return $this->resolvedTypeVisitor->intersection($type, array_map(
            fn(Type $type): Type => $type->accept($this->typeResolver),
            $ofTypes,
        ));
    }

    public function not(Type $type, Type $ofType): mixed
    {
        return $this->resolvedTypeVisitor->not($type, $ofType->accept($this->typeResolver));
    }

    public function mixed(Type $type): mixed
    {
        return $this->resolvedTypeVisitor->mixed($type);
    }
}
