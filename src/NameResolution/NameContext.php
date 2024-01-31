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

    /**
     * @var null|Name|class-string
     */
    private null|Name|string $self = null;

    /**
     * @var null|Name|class-string
     */
    private null|Name|string $parent = null;

    /**
     * @var array<non-empty-string, true>
     */
    private array $templates = [];

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

    public function enterClass(string $name, ?string $parent = null): void
    {
        // TODO: throw if in class

        $this->self = Name::fromString($name);
        $this->parent = Name::fromString($parent);
    }

    /**
     * @param non-empty-string $name
     */
    public function addTemplate(string $name): void
    {
        $this->templates[$name] = true;
    }

    /**
     * @param non-empty-string $name
     */
    public function removeTemplate(string $name): void
    {
        // TODO: throw if no template

        unset($this->templates[$name]);
    }

    public function leaveClass(): void
    {
        // TODO: throw if not in class

        $this->self = null;
        $this->parent = null;
    }

    public function leaveNamespace(): void
    {
        // TODO: throw if in class
        // TODO: throw if not in namespace
        $this->leaveClass();

        $this->namespace = null;
        $this->classImportTable = [];
        $this->constantImportTable = [];
    }

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @template TReturn
     * @param NameResolver<TReturn> $resolver
     * @return TReturn
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
                return $resolver->class($this->resolvedSelf());
            }

            if ($nameAsString === 'parent') {
                if ($this->parent === null) {
                    throw new ReflectionException(sprintf(
                        'Failed to resolve type "parent": class %s does not have parent.',
                        $this->resolvedSelf(),
                    ));
                }

                return $resolver->class($this->resolvedParent());
            }

            if ($nameAsString === 'static') {
                return $resolver->static($this->resolvedSelf());
            }
        }

        if (isset($this->templates[$nameAsString])) {
            return $resolver->template($nameAsString);
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

    /**
     * @return class-string
     */
    private function resolvedSelf(): string
    {
        if (\is_string($this->self)) {
            return $this->self;
        }

        if ($this->self === null) {
            throw new \LogicException('This must never happen.');
        }

        return $this->self = $this->resolveNameAsClass($this->self);
    }

    /**
     * @return class-string
     */
    private function resolvedParent(): string
    {
        if (\is_string($this->parent)) {
            return $this->parent;
        }

        if ($this->parent === null) {
            throw new \LogicException('This must never happen.');
        }

        return $this->parent = $this->resolveNameAsClass($this->parent);
    }
}
