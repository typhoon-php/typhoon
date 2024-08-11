<?php

declare(strict_types=1);

namespace Typhoon\Type\Visitor;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Variance;

/**
 * @api
 * @extends DefaultTypeVisitor<Type>
 */
abstract class RecursiveTypeReplacer extends DefaultTypeVisitor
{
    public function int(Type $type, Type $minType, Type $maxType): mixed
    {
        $newMinType = $minType->accept($this);
        $newMaxType = $maxType->accept($this);

        if ($newMinType === $minType && $newMaxType === $maxType) {
            return $type;
        }

        return types::intRange($newMinType, $newMaxType);
    }

    public function intMask(Type $type, Type $ofType): mixed
    {
        $newOfType = $ofType->accept($this);

        if ($newOfType === $ofType) {
            return $type;
        }

        return types::intMaskOf($newOfType);
    }

    public function float(Type $type, Type $minType, Type $maxType): mixed
    {
        $newMinType = $minType->accept($this);
        $newMaxType = $maxType->accept($this);

        if ($newMinType === $minType && $newMaxType === $maxType) {
            return $type;
        }

        return types::floatRange($newMinType, $newMaxType);
    }

    public function classString(Type $type, Type $classType): mixed
    {
        $newClassType = $classType->accept($this);

        if ($newClassType === $classType) {
            return $type;
        }

        return types::classString($newClassType);
    }

    public function literal(Type $type, Type $ofType): mixed
    {
        $newOfType = $ofType->accept($this);

        if ($newOfType === $ofType) {
            return $type;
        }

        return types::literal($newOfType);
    }

    public function list(Type $type, Type $valueType, array $elements): mixed
    {
        $newValueType = $valueType->accept($this);
        $changed = $newValueType !== $valueType;
        $newElements = [];

        foreach ($elements as $element) {
            $newElementType = $element->type->accept($this);

            if ($newElementType === $element->type) {
                $newElements[] = $element;

                continue;
            }

            $newElements[] = $element->with(type: $newElementType);
            $changed = true;
        }

        if ($changed) {
            return types::unsealedListShape($newElements, $newValueType);
        }

        return $type;
    }

    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
    {
        $newKeyType = $keyType->accept($this);
        $newValueType = $valueType->accept($this);
        $changed = $newKeyType !== $keyType || $newValueType !== $valueType;
        $newElements = [];

        foreach ($elements as $key => $element) {
            $newElementType = $element->type->accept($this);

            if ($newElementType === $element->type) {
                $newElements[$key] = $element;

                continue;
            }

            $newElements[$key] = $element->with(type: $newElementType);
            $changed = true;
        }

        if ($changed) {
            return types::unsealedArrayShape($newElements, $newKeyType, $newValueType);
        }

        return $type;
    }

    public function key(Type $type, Type $arrayType): mixed
    {
        $newArrayType = $arrayType->accept($this);

        if ($newArrayType === $arrayType) {
            return $type;
        }

        return types::keyOf($newArrayType);
    }

    public function offset(Type $type, Type $arrayType, Type $keyType): mixed
    {
        $newArrayType = $arrayType->accept($this);
        $newKeyType = $keyType->accept($this);

        if ($newArrayType === $arrayType && $newKeyType === $keyType) {
            return $type;
        }

        return types::offset($newArrayType, $newKeyType);
    }

    public function iterable(Type $type, Type $keyType, Type $valueType): mixed
    {
        $newKeyType = $keyType->accept($this);
        $newValueType = $valueType->accept($this);

        if ($newKeyType === $keyType && $newValueType === $valueType) {
            return $type;
        }

        return types::iterable($newKeyType, $newValueType);
    }

    public function object(Type $type, array $properties): mixed
    {
        $changed = false;
        $newProperties = [];

        foreach ($properties as $name => $property) {
            $newPropertyType = $property->type->accept($this);

            if ($newPropertyType === $property->type) {
                $newProperties[$name] = $property;

                continue;
            }

            $newProperties[$name] = $property->with(type: $newPropertyType);
            $changed = true;
        }

        if ($changed) {
            return types::objectShape($newProperties);
        }

        return $type;
    }

    public function namedObject(Type $type, NamedClassId|AnonymousClassId $classId, array $typeArguments): mixed
    {
        $newTypeArguments = $this->replaceTypes($typeArguments);

        if ($newTypeArguments === $typeArguments) {
            return $type;
        }

        return types::object($classId, $newTypeArguments);
    }

    public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        $newTypeArguments = $this->replaceTypes($typeArguments);

        if ($newTypeArguments === $typeArguments) {
            return $type;
        }

        return types::self($newTypeArguments, $resolvedClassId);
    }

    public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClassId): mixed
    {
        $newTypeArguments = $this->replaceTypes($typeArguments);

        if ($newTypeArguments === $typeArguments) {
            return $type;
        }

        return types::parent($newTypeArguments, $resolvedClassId);
    }

    public function static(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        $newTypeArguments = $this->replaceTypes($typeArguments);

        if ($newTypeArguments === $typeArguments) {
            return $type;
        }

        return types::static($newTypeArguments, $resolvedClassId);
    }

    public function callable(Type $type, array $parameters, Type $returnType): mixed
    {
        $newReturnType = $returnType->accept($this);
        $changed = $newReturnType !== $returnType;
        $newParameters = [];

        foreach ($parameters as $parameter) {
            $newParameterType = $parameter->type->accept($this);

            if ($newParameterType === $parameter->type) {
                $newParameters[] = $parameter;

                continue;
            }

            $newParameters[] = $parameter->with(type: $newParameterType);
            $changed = true;
        }

        if ($changed) {
            return types::callable($newParameters, $newReturnType);
        }

        return $type;
    }

    public function classConstant(Type $type, Type $classType, string $name): mixed
    {
        $newClassType = $classType->accept($this);

        if ($newClassType === $classType) {
            return $type;
        }

        return types::classConstant($newClassType, $name);
    }

    public function classConstantMask(Type $type, Type $classType, string $namePrefix): mixed
    {
        $newClassType = $classType->accept($this);

        if ($newClassType === $classType) {
            return $type;
        }

        return types::classConstantMask($newClassType, $namePrefix);
    }

    public function alias(Type $type, AliasId $aliasId, array $typeArguments): mixed
    {
        $newTypeArguments = $this->replaceTypes($typeArguments);

        if ($newTypeArguments === $typeArguments) {
            return $type;
        }

        return types::alias($aliasId, $newTypeArguments);
    }

    public function varianceAware(Type $type, Type $ofType, Variance $variance): mixed
    {
        $newOfType = $ofType->accept($this);

        if ($newOfType === $ofType) {
            return $type;
        }

        return types::varianceAware($newOfType, $variance);
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        $newOfTypes = $this->replaceTypes($ofTypes);

        if ($newOfTypes === $ofTypes) {
            return $type;
        }

        return types::union(...$newOfTypes);
    }

    public function conditional(Type $type, Type $subjectType, Type $ifType, Type $thenType, Type $elseType): mixed
    {
        $newSubjectType = $subjectType->accept($this);
        $newIfType = $ifType->accept($this);
        $newThenType = $thenType->accept($this);
        $newElseType = $elseType->accept($this);

        if ($newSubjectType === $subjectType && $newIfType === $ifType && $newThenType === $thenType && $newElseType === $elseType) {
            return $type;
        }

        return types::conditional($newSubjectType, $newIfType, $newThenType, $newElseType);
    }

    public function intersection(Type $type, array $ofTypes): mixed
    {
        $newOfTypes = $this->replaceTypes($ofTypes);

        if ($newOfTypes === $ofTypes) {
            return $type;
        }

        return types::intersection(...$newOfTypes);
    }

    public function not(Type $type, Type $ofType): mixed
    {
        $newOfType = $ofType->accept($this);

        if ($newOfType === $ofType) {
            return $type;
        }

        return types::not($newOfType);
    }

    /**
     * @param list<Type> $types
     * @return list<Type>
     */
    final protected function replaceTypes(array $types): array
    {
        return array_map(fn(Type $type): Type => $type->accept($this), $types);
    }

    protected function default(Type $type): mixed
    {
        return $type;
    }
}
