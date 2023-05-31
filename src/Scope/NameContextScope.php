<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Type\ClassTemplateType;
use ExtendedTypeSystem\Type\FunctionTemplateType;
use ExtendedTypeSystem\Type\MethodTemplateType;
use PhpParser\NameContext;
use PhpParser\Node\Name;

/**
 * @api
 */
final class NameContextScope implements Scope
{
    public function __construct(
        private readonly NameContext $nameContext,
    ) {
    }

    public function resolveClass(Name $name): string
    {
        /** @var class-string */
        return $this->nameContext->getResolvedClassName($name)->toString();
    }

    public function tryResolveTemplate(string $name): null|FunctionTemplateType|ClassTemplateType|MethodTemplateType
    {
        return null;
    }
}
