<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NewApi;

use Typhoon\Reflection\Origin;

/**
 * @api
 * @template-covariant TObject of object
 */
abstract class ClassReflection
{
    /**
     * @param class-string<TObject> $name
     * @param ?non-empty-string $file
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     * @param ?non-empty-string $extension
     * @param ?non-empty-string $phpDoc
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $file,
        public readonly ?int $startLine,
        public readonly ?int $endLine,
        public readonly ?string $extension,
        public readonly array $interfaceNames,
        public readonly array $traitNames,
        public readonly ?string $phpDoc,
    ) {}

    abstract public function namespace(): string;

    /**
     * @return non-empty-string
     */
    abstract public function shortName(): string;

    abstract public function isInterface(): bool;

    abstract public function isEnum(): bool;

    abstract public function isTrait(): bool;

    abstract public function isInternal(): bool;

    abstract public function isDeprecated(): bool;

    abstract public function isAbstract(): bool;

    abstract public function isAnonymous(): bool;

    abstract public function isCloneable(): bool;

    abstract public function isInstantiable(): bool;

    abstract public function isIterable(): bool;

    abstract public function isFinal(Origin $origin = Origin::Resolved): bool;

    abstract public function isReadonly(Origin $origin = Origin::Resolved): bool;

    abstract public function parent(): ?self;

    /**
     * @return list<self>
     */
    abstract public function interfaces(): array;

    /**
     * @return list<self>
     */
    abstract public function traits(): array;

    abstract public function typeAlias(string $name): ?TypeAliasReflection;

    /**
     * @param ?callable(TypeAliasReflection): bool $filter
     * @return list<TypeAliasReflection>
     */
    abstract public function typeAliases(?callable $filter = null): array;

    abstract public function template(string $nameOrIndex): ?TemplateReflection;

    /**
     * @param ?callable(TemplateReflection): bool $filter
     * @return list<TemplateReflection>
     */
    abstract public function templates(?callable $filter = null): array;

    abstract public function constant(string $name): ?ConstantReflection;

    /**
     * @param ?callable(ConstantReflection): bool $filter
     * @return list<ConstantReflection>
     */
    abstract public function constants(?callable $filter = null): array;

    abstract public function property(string $name): ?PropertyReflection;

    /**
     * @param ?callable(PropertyReflection): bool $filter
     * @return list<PropertyReflection>
     */
    abstract public function properties(?callable $filter = null): array;

    abstract public function method(string $name): ?MethodReflection;

    /**
     * @param ?callable(MethodReflection): bool $filter
     * @return list<MethodReflection>
     */
    abstract public function methods(?callable $filter = null): array;
}
