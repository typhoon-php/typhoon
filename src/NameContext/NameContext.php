<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use Typhoon\Reflection\ReflectionException;

/**
 * Inspired by PhpParser\NameContext.
 * Designed according to https://www.php.net/manual/en/language.namespaces.rules.php.
 *
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TTemplateMetadata
 */
final class NameContext implements NameResolver
{
    private const SELF = 'self';
    private const PARENT = 'parent';
    private const STATIC = 'static';
    private const SPECIAL = [
        self::SELF => true,
        self::PARENT => true,
        self::STATIC => true,
    ];

    private null|UnqualifiedName|QualifiedName $namespace = null;

    /**
     * @var array<non-empty-string, UnqualifiedName|QualifiedName>
     */
    private array $namespaceImportTable = [];

    /**
     * @var array<non-empty-string, UnqualifiedName|QualifiedName>
     */
    private array $constantImportTable = [];

    /**
     * @var array<non-empty-string, UnqualifiedName|QualifiedName>
     */
    private array $functionImportTable = [];

    /**
     * @psalm-pure
     */
    private static function parse(string $name): UnqualifiedName|QualifiedName|RelativeName|FullyQualifiedName
    {
        $segments = explode('\\', $name);

        return match ($segments[0] ?? null) {
            '' => new FullyQualifiedName(self::segmentsToName(\array_slice($segments, 1))),
            'namespace' => new RelativeName(self::segmentsToName(\array_slice($segments, 1))),
            default => self::segmentsToName($segments),
        };
    }

    /**
     * @psalm-pure
     * @param list<string> $segments
     */
    private static function segmentsToName(array $segments): UnqualifiedName|QualifiedName
    {
        if (\count($segments) === 0) {
            throw new \InvalidArgumentException('Empty name.');
        }

        if (\count($segments) === 1) {
            return new UnqualifiedName($segments[0]);
        }

        return new QualifiedName(array_map(
            static fn(string $segment): UnqualifiedName => new UnqualifiedName($segment),
            $segments,
        ));
    }

    public function enterNamespace(?string $namespace = null): void
    {
        $this->leaveNamespace();

        if ($namespace !== null) {
            $this->namespace = self::parse($namespace)->resolve();
        }
    }

    public function addUse(string $name, ?string $alias = null): void
    {
        $resolvedName = self::parse($name)->resolve();
        $resolvedAlias = ($alias === null ? $resolvedName->lastSegment() : new UnqualifiedName($alias))->toString();

        if (isset($this->namespaceImportTable[$resolvedAlias])) {
            throw new ReflectionException(sprintf(
                'Cannot use %s as %s because the name is already in use.',
                $name,
                $resolvedAlias,
            ));
        }

        $this->namespaceImportTable[$resolvedAlias] = $resolvedName;
    }

    public function addConstantUse(string $name, ?string $alias = null): void
    {
        $resolvedName = self::parse($name)->resolve();
        $resolvedAlias = ($alias === null ? $resolvedName->lastSegment() : new UnqualifiedName($alias))->toString();

        if (isset($this->constantImportTable[$resolvedAlias])) {
            throw new ReflectionException(sprintf(
                'Cannot use constant %s as %s because the name is already in use.',
                $name,
                $resolvedAlias,
            ));
        }

        $this->constantImportTable[$resolvedAlias] = $resolvedName;
    }

    public function addFunctionUse(string $name, ?string $alias = null): void
    {
        $resolvedName = self::parse($name)->resolve();
        $resolvedAlias = ($alias === null ? $resolvedName->lastSegment() : new UnqualifiedName($alias))->toString();

        if (isset($this->functionImportTable[$resolvedAlias])) {
            throw new ReflectionException(sprintf(
                'Cannot use constant %s as %s because the name is already in use.',
                $name,
                $resolvedAlias,
            ));
        }

        $this->functionImportTable[$resolvedAlias] = $resolvedName;
    }

    public function enterClass(string $name, ?string $parent = null): void
    {
        $this->leaveClass();

        $this->namespaceImportTable[self::SELF] = self::parse($name)->resolve($this->namespace, $this->namespaceImportTable);

        if ($parent !== null) {
            $this->namespaceImportTable[self::PARENT] = self::parse($parent)->resolve($this->namespace, $this->namespaceImportTable);
        }
    }

    public function leaveClass(): void
    {
        unset($this->namespaceImportTable[self::SELF], $this->namespaceImportTable[self::PARENT]);
    }

    public function leaveNamespace(): void
    {
        $this->namespace = null;
        $this->namespaceImportTable = [];
        $this->constantImportTable = [];
        $this->functionImportTable = [];
    }

    public function resolveNameAsClass(string $name): string
    {
        if (!isset($this->namespaceImportTable[self::SELF]) && isset(self::SPECIAL[strtolower($name)])) {
            throw new \InvalidArgumentException(sprintf('%s cannot be used outside of the class scope.', $name));
        }

        /** @var class-string */
        return self::parse($name)->resolve($this->namespace, $this->namespaceImportTable)->toString();
    }

    public function resolveNameAsConstant(string $name): array
    {
        if (strtolower($name) === self::STATIC) {
            throw new \InvalidArgumentException('static is not a valid constant name.');
        }

        $name = self::parse($name);

        if (!$name instanceof UnqualifiedName) {
            return [$name->resolve($this->namespace, $this->namespaceImportTable)->toString()];
        }

        $nameAsString = $name->toString();

        if (isset($this->constantImportTable[$nameAsString])) {
            return [$this->constantImportTable[$nameAsString]->toString()];
        }

        if ($this->namespace === null) {
            return [$nameAsString];
        }

        return [$name->resolve($this->namespace)->toString(), $nameAsString];
    }
}
