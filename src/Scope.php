<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type\ClassTemplateType;
use ExtendedTypeSystem\Type\FunctionTemplateType;
use ExtendedTypeSystem\Type\MethodTemplateType;
use PhpParser\Node\Name;

/**
 * @api
 */
interface Scope
{
    public const SELF = 'self';
    public const PARENT = 'parent';

    /**
     * @return class-string
     */
    public function resolveClass(Name $name): string;

    /**
     * @param non-empty-string $name
     */
    public function tryResolveTemplate(string $name): null|FunctionTemplateType|ClassTemplateType|MethodTemplateType;
}
