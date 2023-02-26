<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\Reflection;

use PHP\ExtendedTypeSystem\Type\NamedObjectT;

/**
 * @psalm-api
 */
final class NullTemplateReflector implements TemplateReflector
{
    public function reflectFunctionTemplates(string $function): array
    {
        return [];
    }

    public function reflectClassTemplates(string $class): array
    {
        return [];
    }

    public function reflectClassTemplateExtends(string $class): ?NamedObjectT
    {
        return null;
    }

    public function reflectClassTemplateImplements(string $class): array
    {
        return [];
    }

    public function reflectMethodTemplates(string $class, string $method): array
    {
        return [];
    }
}
