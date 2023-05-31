<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Type\ClassTemplateType;
use ExtendedTypeSystem\Type\FunctionTemplateType;
use ExtendedTypeSystem\Type\MethodTemplateType;
use PhpParser\Node\Name;

/**
 * @api
 */
final class PropertyScope implements Scope
{
    public function __construct(
        private readonly Scope $classScope,
        private readonly bool $static = false,
    ) {
    }

    public function resolveClass(Name $name): string
    {
        return $this->classScope->resolveClass($name);
    }

    public function tryResolveTemplate(string $name): null|FunctionTemplateType|ClassTemplateType|MethodTemplateType
    {
        if ($this->static) {
            return null;
        }

        return $this->classScope->tryResolveTemplate($name);
    }
}
