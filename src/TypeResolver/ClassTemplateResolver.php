<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Type;
use Typhoon\TypeResolver;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
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
    ) {}

    public function visitClassTemplate(Type\ClassTemplateType $type): mixed
    {
        if ($type->class === $this->class && isset($this->templateArguments[$type->name])) {
            return $this->templateArguments[$type->name];
        }

        return $type;
    }
}
