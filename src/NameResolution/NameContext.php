<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameResolution;

use Typhoon\Reflection\ReflectionException;

/**
 * Inspired by PhpParser\NameContext.
 *
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NameContext
{
    private null|UnqualifiedName|QualifiedName $namespace = null;

    /**
     * @var array<non-empty-string, UnqualifiedName|QualifiedName>
     */
    private array $classImportTable = [];

    /**
     * @var array<non-empty-string, UnqualifiedName|QualifiedName>
     */
    private array $constantImportTable = [];

    private ?Name $self = null;

    private ?Name $parent = null;

    /**
     * @var array<non-empty-string, true>
     */
    private array $classTemplateNamesMap = [];

    /**
     * @var array<non-empty-string, true>
     */
    private array $methodTemplateNamesMap = [];

    public function enterNamespace(?string $namespace): void
    {
        $this->leaveNamespace();

        $this->namespace = Name::fromString($namespace)?->resolveInNamespace();
    }

    public function addUse(string $name, string $alias, ?string $prefix = null): void
    {
        $alias = (new UnqualifiedName($alias))->toString();

        if (isset($this->classImportTable[$alias])) {
            throw new ReflectionException(sprintf(
                'Cannot use %s as %s because the name is already in use.',
                $name,
                $alias,
            ));
        }

        $this->classImportTable[$alias] = Name::concatenate(
            Name::fromString($prefix)?->resolveInNamespace(),
            Name::fromString($name)->resolveInNamespace(),
        );
    }

    public function addConstantUse(string $name, string $alias, ?string $prefix = null): void
    {
        $alias = (new UnqualifiedName($alias))->toString();

        if (isset($this->constantImportTable[$alias])) {
            throw new ReflectionException(sprintf(
                'Cannot use constant %s as %s because the name is already in use.',
                $name,
                $alias,
            ));
        }

        $this->constantImportTable[$alias] = Name::concatenate(
            Name::fromString($prefix)?->resolveInNamespace(),
            Name::fromString($name)->resolveInNamespace(),
        );
    }

    /**
     * @param array<string> $templateNames
     */
    public function enterClass(string $name, ?string $parent = null, array $templateNames = []): void
    {
        $this->leaveClass();

        $this->self = Name::fromString($name);
        $this->parent = Name::fromString($parent);
        $this->classTemplateNamesMap = array_fill_keys(
            array_map(
                /** @return non-empty-string */
                static fn(string $templateName): string => (new UnqualifiedName($templateName))->toString(),
                $templateNames,
            ),
            true,
        );
    }

    /**
     * @param array<string> $templateNames
     */
    public function enterMethod(array $templateNames = []): void
    {
        $this->leaveMethod();

        $this->methodTemplateNamesMap = array_fill_keys(
            array_map(
                /** @return non-empty-string */
                static fn(string $templateName): string => (new UnqualifiedName($templateName))->toString(),
                $templateNames,
            ),
            true,
        );
    }

    public function leaveMethod(): void
    {
        $this->methodTemplateNamesMap = [];
    }

    public function leaveClass(): void
    {
        $this->leaveMethod();

        $this->self = null;
        $this->parent = null;
        $this->classTemplateNamesMap = [];
    }

    public function leaveNamespace(): void
    {
        $this->leaveClass();

        $this->namespace = null;
        $this->classImportTable = [];
        $this->constantImportTable = [];
    }

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @template T
     * @param NameResolver<T> $resolver
     * @return T
     */
    public function resolveName(string|Name $name, NameResolver $resolver): mixed
    {
        if (\is_string($name)) {
            $name = Name::fromString($name);
        }

        $lastSegmentIsSpecialClassType = \in_array($name->lastSegment()->toString(), ['self', 'parent'], true);

        if ($name instanceof FullyQualifiedName) {
            $resolvedName = $name->resolveInNamespace($this->namespace);

            if ($lastSegmentIsSpecialClassType) {
                return $resolver->constant($resolvedName->toString());
            }

            return $resolver->classOrConstants($resolvedName->toString(), [$resolvedName->toString()]);
        }

        if ($name instanceof RelativeName) {
            $resolvedName = $name->resolveInNamespace($this->namespace);

            if ($lastSegmentIsSpecialClassType) {
                return $resolver->constant($resolvedName->toString());
            }

            return $resolver->classOrConstants($resolvedName->toString(), [$resolvedName->toString()]);
        }

        if ($name instanceof QualifiedName) {
            $firstSegmentAsString = $name->firstSegment()->toString();

            if (isset($this->classImportTable[$firstSegmentAsString])) {
                $resolvedName = $name->withFirstSegmentReplaced($this->classImportTable[$firstSegmentAsString]);
            } else {
                $resolvedName = $name->resolveInNamespace($this->namespace);
            }

            if ($lastSegmentIsSpecialClassType) {
                return $resolver->constant($resolvedName->toString());
            }

            return $resolver->classOrConstants($resolvedName->toString(), [$resolvedName->toString()]);
        }

        if (!$name instanceof UnqualifiedName) {
            throw new ReflectionException(sprintf('Name %s is not supported.', $name::class));
        }

        $nameAsString = $name->toString();

        if ($this->self !== null) {
            if ($nameAsString === 'self') {
                return $resolver->class($this->resolveNameAsClass($this->self));
            }

            if ($nameAsString === 'parent') {
                if ($this->parent === null) {
                    throw new ReflectionException(sprintf(
                        'Failed to resolve type "parent": class %s does not have parent.',
                        $this->resolveNameAsClass($this->self),
                    ));
                }

                return $resolver->class($this->resolveNameAsClass($this->parent));
            }

            if ($nameAsString === 'static') {
                return $resolver->static($this->resolveNameAsClass($this->self));
            }

            if (isset($this->methodTemplateNamesMap[$nameAsString])) {
                return $resolver->template($nameAsString);
            }

            if (isset($this->classTemplateNamesMap[$nameAsString])) {
                return $resolver->template($nameAsString);
            }
        }

        if (isset($this->classImportTable[$nameAsString])) {
            /** @var class-string */
            $class = $this->classImportTable[$nameAsString]->toString();

            return $resolver->class($class);
        }

        if (isset($this->constantImportTable[$nameAsString])) {
            return $resolver->constant($this->constantImportTable[$nameAsString]->toString());
        }

        if ($this->namespace === null) {
            return $resolver->classOrConstants($nameAsString, [$nameAsString]);
        }

        $resolvedName = $name->resolveInNamespace($this->namespace);

        return $resolver->classOrConstants($resolvedName->toString(), [$resolvedName->toString(), $nameAsString]);
    }

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @return class-string
     */
    public function resolveNameAsClass(string|Name $name): string
    {
        return $this->resolveName($name, new NameAsClassResolver());
    }
}
