<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeResolver;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeResolver;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 * @psalm-immutable
 */
final class ClassTemplateResolver extends TypeResolver
{
    /**
     * @param class-string $class
     * @param non-empty-array<non-empty-string, Type> $templateArguments
     */
    public function __construct(
        private readonly string $class,
        private readonly array $templateArguments,
    ) {
    }

    public function visitClassTemplate(Type\ClassTemplateType $type): mixed
    {
        if ($type->class === $this->class && isset($this->templateArguments[$type->name])) {
            return $this->templateArguments[$type->name];
        }

        return $type;
    }
}
