<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\TypeReflection;

use PHP\ExtendedTypeSystem\Type\NamedObjectT;

/**
 * @psalm-api
 */
interface TemplateReflector
{
    /**
     * @param callable-string $function
     * @return list<Template>
     */
    public function reflectFunctionTemplates(string $function): array;

    /**
     * @param class-string $class
     * @return list<Template>
     */
    public function reflectClassTemplates(string $class): array;

    /**
     * @param class-string $class
     * @return ?NamedObjectT
     */
    public function reflectClassTemplateExtends(string $class): ?NamedObjectT;

    /**
     * @param class-string $class
     * @return list<NamedObjectT>
     */
    public function reflectClassTemplateImplements(string $class): array;

    /**
     * @param class-string $class
     * @return list<Template>
     */
    public function reflectMethodTemplates(string $class, string $method): array;
}
