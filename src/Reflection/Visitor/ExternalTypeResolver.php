<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\DeclarationId\AliasId;
use Typhoon\Reflection\ClassConstantReflection;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Visitor\RecursiveTypeReplacer;

/**
 * @api
 */
final class ExternalTypeResolver extends RecursiveTypeReplacer
{
    public function __construct(
        private readonly TyphoonReflector $reflector,
    ) {}

    public function alias(Type $type, AliasId $aliasId, array $typeArguments): mixed
    {
        // todo apply $typeArguments https://github.com/typhoon-php/typhoon/issues/65
        return $this->reflector->reflect($aliasId)->type()->accept($this);
    }

    public function classConstant(Type $type, Type $classType, string $name): mixed
    {
        $classId = $classType->accept($this)->accept(new ClassIdResolver());

        return $this->reflector->reflect($classId)->constants()[$name]->type()->accept($this);
    }

    public function classConstantMask(Type $type, Type $classType, string $namePrefix): mixed
    {
        $classId = $classType->accept($this)->accept(new ClassIdResolver());

        $types = $this->reflector
            ->reflect($classId)
            ->constants()
            ->filter(static fn(ClassConstantReflection $_constant, string $name): bool => str_starts_with($name, $namePrefix))
            ->map(static fn(ClassConstantReflection $constant): Type => $constant->type())
            ->toList();

        return types::union(...$types);
    }
}
