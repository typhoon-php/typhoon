<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Reflection\TypeReflectionException;
use ExtendedTypeSystem\Type\ClassTemplateType;
use ExtendedTypeSystem\Type\FunctionTemplateType;
use ExtendedTypeSystem\Type\MethodTemplateType;
use PhpParser\Node\Name;

/**
 * @api
 */
final class GlobalScope implements Scope
{
    public function resolveClass(Name $name): string
    {
        $nameAsString = $name->toString();

        if ($nameAsString === self::SELF) {
            throw new TypeReflectionException('Cannot resolve self in global scope.');
        }

        if ($nameAsString === self::PARENT) {
            throw new TypeReflectionException('Cannot resolve parent in global scope.');
        }

        /** @var class-string */
        return $nameAsString;
    }

    public function tryResolveTemplate(string $name): null|FunctionTemplateType|ClassTemplateType|MethodTemplateType
    {
        return null;
    }
}
