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
final class ClassLikeScope implements Scope
{
    /**
     * @param class-string $name
     * @param ?class-string $parent
     * @param list<non-empty-string> $templateNames
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $parent = null,
        private readonly array $templateNames = [],
        private readonly Scope $parentScope = new GlobalScope(),
    ) {
    }

    public function resolveClass(Name $name): string
    {
        $nameAsString = $name->toString();

        if ($nameAsString === self::SELF) {
            return $this->name;
        }

        if ($nameAsString === self::PARENT) {
            return $this->parent ?? throw new \LogicException(sprintf(
                'Failed to resolve name "parent": class %s does not have a parent.',
                $this->name,
            ));
        }

        return $this->parentScope->resolveClass($name);
    }

    public function tryResolveTemplate(string $name): null|FunctionTemplateType|ClassTemplateType|MethodTemplateType
    {
        if (\in_array($name, $this->templateNames, true)) {
            return types::classTemplate($name, $this->name);
        }

        return null;
    }
}
