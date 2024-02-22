<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TemplateResolver extends RecursiveTypeReplacer
{
    /**
     * @param array<non-empty-string, Type> $templateArguments
     */
    private function __construct(
        private readonly array $templateArguments,
    ) {}

    /**
     * @param array<TemplateReflection> $templates
     * @param array<Type> $templateArguments
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

    public function template(Type $self, string $name, AtClass|AtFunction|AtMethod $declaredAt): mixed
    {
        return $this->templateArguments[$name] ?? $self;
    }
}
