<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeAlias;

use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\TypeResolver\RecursiveTypeReplacer;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ImportedTypeResolver extends RecursiveTypeReplacer
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
    ) {}

    public function visitImportedType(ImportedType $type): Type
    {
        return $this->classReflector->reflectClass($type->class)->getTypeAlias($type->name);
    }
}
