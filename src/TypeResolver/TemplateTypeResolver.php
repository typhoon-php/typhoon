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
final class TemplateTypeResolver extends RecursiveTypeReplacer
{
    /**
     * @param non-empty-array<non-empty-string, Type\Type> $templateArguments
     */
    private function __construct(
        private readonly array $templateArguments,
    ) {}

    /**
     * @psalm-pure
     * @param array<TemplateReflection> $templates
     * @param array<Type\Type> $templateArguments
     * @return Type\TypeVisitor<Type\Type>
     */
    public static function create(array $templates, array $templateArguments): Type\TypeVisitor
    {
        if ($templates === []) {
            return new IdentityTypeReplacer();
        }

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
