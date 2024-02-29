<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TemplateResolver extends RecursiveTypeReplacer
{
    /**
     * @param array<non-empty-string, Type> $templateArguments
     * @param non-empty-string $self
     * @param ?non-empty-string $parent
     */
    private function __construct(
        private readonly array $templateArguments,
        private readonly string $self,
        private readonly ?string $parent = null,
        private readonly bool $resolveStatic = false,
    ) {}

    /**
     * @param array<TemplateReflection> $templates
     * @param array<Type> $templateArguments
     * @param non-empty-string $self
     * @param ?non-empty-string $parent
     */
    public static function create(array $templates, array $templateArguments, string $self, ?string $parent = null, bool $resolveStatic = false): self
    {
        $resolvedTemplateArguments = [];

        foreach ($templates as $template) {
            $resolvedTemplateArguments[$template->name] = $templateArguments[$template->name]
                ?? $templateArguments[$template->getPosition()]
                ?? $template->getConstraint();
        }

        return new self($resolvedTemplateArguments, $self, $parent, $resolveStatic);
    }

    public function template(Type $self, string $name, AtClass|AtFunction|AtMethod $declaredAt, array $arguments): mixed
    {
        $arguments = array_map(
            fn(Type $templateArgument): Type => $templateArgument->accept($this),
            $arguments,
        );

        if ($name === 'self') {
            return types::object($this->self, ...$arguments);
        }

        if ($name === 'parent' && $this->parent !== null) {
            return types::object($this->parent, ...$arguments);
        }

        if ($name === 'static') {
            if ($this->resolveStatic) {
                return types::object($this->self, ...$arguments);
            }

            return types::template($name, types::atClass($this->self), ...$arguments);
        }

        return $this->templateArguments[$name] ?? $self;
    }
}
