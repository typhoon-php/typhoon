<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeAlias;

use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\TypeResolver\RecursiveTypeReplacer;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class ImportedTypeAliasReplacer extends RecursiveTypeReplacer
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
    ) {}

    /**
     * @psalm-suppress ImpureMethodCall
     */
    public function visitImportedType(ImportedTypeAlias $type): Type
    {
        return $this->classReflector->reflectClass($type->class)->getTypeAlias($type->name);
    }
}
