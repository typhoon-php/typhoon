<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\PassedBy;
use Typhoon\Reflection\Internal\NativeAdapter\ParameterAdapter;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @psalm-import-type Attributes from ReflectionCollections
 */
final class ParameterReflection
{
    public readonly ParameterId $id;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @var ?Attributes
     */
    private ?Collection $attributes = null;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        ParameterId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * @return non-negative-int
     */
    public function index(): int
    {
        return $this->data[Data::Index];
    }

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    /**
     * @return AttributeReflection[]
     * @psalm-return Attributes
     * @phpstan-return Attributes
     */
    public function attributes(): Collection
    {
        return $this->attributes ??= (new Collection($this->data[Data::Attributes]))
            ->map(fn(TypedMap $data, int $index): AttributeReflection => new AttributeReflection($this->id, $index, $data, $this->reflector));
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc]?->getText();
    }

    public function function(): FunctionReflection|MethodReflection
    {
        return $this->reflector->reflect($this->id->function);
    }

    public function class(): ?ClassReflection
    {
        if ($this->id->function instanceof MethodId) {
            return $this->reflector->reflect($this->id->function->class);
        }

        return null;
    }

    public function hasDefaultValue(): bool
    {
        return $this->data[Data::DefaultValueExpression] !== null;
    }

    public function defaultValue(): mixed
    {
        return $this->data[Data::DefaultValueExpression]?->evaluate($this->reflector);
    }

    public function isNative(): bool
    {
        return !$this->isAnnotated();
    }

    public function isAnnotated(): bool
    {
        return $this->data[Data::Annotated];
    }

    public function isOptional(): bool
    {
        return $this->data[Data::Optional];
    }

    public function canBePassedByValue(): bool
    {
        return \in_array($this->data[Data::PassedBy], [PassedBy::Value, PassedBy::ValueOrReference], true);
    }

    public function canBePassedByReference(): bool
    {
        return \in_array($this->data[Data::PassedBy], [PassedBy::Reference, PassedBy::ValueOrReference], true);
    }

    /**
     * @psalm-assert-if-true !null $this->promotedParameter()
     */
    public function isPromoted(): bool
    {
        return $this->data[Data::Promoted];
    }

    public function isVariadic(): bool
    {
        return $this->data[Data::Variadic];
    }

    /**
     * @return ($kind is null ? Type : ?Type)
     */
    public function type(?DeclarationKind $kind = null): ?Type
    {
        return $this->data[Data::Type]->get($kind);
    }

    public function isDeprecated(): bool
    {
        return $this->data[Data::Deprecation] !== null;
    }

    public function deprecation(): ?Deprecation
    {
        return $this->data[Data::Deprecation];
    }

    private ?ParameterAdapter $native = null;

    public function toNativeReflection(): \ReflectionParameter
    {
        return $this->native ??= new ParameterAdapter($this, $this->reflector);
    }
}
