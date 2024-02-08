<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 */
final class TemplateResolver extends RecursiveTypeReplacer
{
    /**
     * @param array<non-empty-string, Type\Type> $templateArguments
     */
    private function __construct(
        private readonly array $templateArguments,
    ) {}

    /**
     * @psalm-pure
     * @param array<TemplateReflection> $templates
     * @param array<Type\Type> $templateArguments
     */
    public static function create(array $templates, array $templateArguments): self
    {
        $resolvedTemplateArguments = [];

        foreach ($templates as $template) {
            $resolvedTemplateArguments[$template->name] = $templateArguments[$template->name]
                ?? $templateArguments[$template->getPosition()]
                ?? $template->getConstraint();
        }

        return new self($resolvedTemplateArguments);
    }

    public function visitTemplate(Type\TemplateType $type): mixed
    {
        return $this->templateArguments[$type->name] ?? $type;
    }
}
