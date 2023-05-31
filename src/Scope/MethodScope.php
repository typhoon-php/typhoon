<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Scope;

use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Type\ClassTemplateType;
use ExtendedTypeSystem\Type\FunctionTemplateType;
use ExtendedTypeSystem\Type\MethodTemplateType;
use ExtendedTypeSystem\types;
use PhpParser\Node\Name;

/**
 * @api
 */
final class MethodScope implements Scope
{
    /**
     * @param non-empty-string $name
     * @param list<non-empty-string> $templateNames
     */
    public function __construct(
        private readonly Scope $classScope,
        private readonly string $name,
        private readonly bool $static = false,
        private readonly array $templateNames = [],
    ) {
    }

    public function resolveClass(Name $name): string
    {
        return $this->classScope->resolveClass($name);
    }

    public function tryResolveTemplate(string $name): null|FunctionTemplateType|ClassTemplateType|MethodTemplateType
    {
        if (\in_array($name, $this->templateNames, true)) {
            return types::methodTemplate($name, $this->classScope->resolveClass(new Name(self::SELF)), $this->name);
        }

        if ($this->static) {
            return null;
        }

        return $this->classScope->tryResolveTemplate($name);
    }
}
