<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\Misc\NonSerializable;
use Typhoon\Reflection\Internal\NativeAdapter\PropertyAdapter;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @psalm-import-type Attributes from ReflectionCollections
 */
final class PropertyReflection
{
    use NonSerializable;

    public readonly PropertyId $id;

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
        PropertyId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->id = $id;
        $this->data = $data;
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

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    public function isNative(): bool
    {
        return !$this->isAnnotated();
    }

    public function isAnnotated(): bool
    {
        return $this->data[Data::Annotated];
    }

    public function isStatic(): bool
    {
        return $this->data[Data::Static];
    }

    /**
     * @psalm-assert-if-true !null $this->promotedParameter()
     */
    public function isPromoted(): bool
    {
        return $this->data[Data::Promoted];
    }

    /**
     * This method returns the actual property's default value and thus might trigger autoloading or throw errors.
     */
    public function evaluateDefault(): mixed
    {
        return $this->data[Data::DefaultValueExpression]?->evaluate($this->reflector);
    }

    /**
     * @deprecated since 0.4.2 in favor of evaluateDefault()
     */
    public function defaultValue(): mixed
    {
        trigger_deprecation('typhoon/reflection', '0.4.2', 'Calling %s is deprecated in favor of %s::evaluateDefault()', __METHOD__, self::class);

        return $this->evaluateDefault();
    }

    public function hasDefaultValue(): bool
    {
        return $this->data[Data::DefaultValueExpression] !== null;
    }

    public function isPrivate(): bool
    {
        return $this->data[Data::Visibility] === Visibility::Private;
    }

    public function isProtected(): bool
    {
        return $this->data[Data::Visibility] === Visibility::Protected;
    }

    public function isPublic(): bool
    {
        $visibility = $this->data[Data::Visibility];

        return $visibility === null || $visibility === Visibility::Public;
    }

    public function isReadonly(ModifierKind $kind = ModifierKind::Resolved): bool
    {
        return match ($kind) {
            ModifierKind::Resolved => $this->data[Data::NativeReadonly] || $this->data[Data::AnnotatedReadonly],
            ModifierKind::Native => $this->data[Data::NativeReadonly],
            ModifierKind::Annotated => $this->data[Data::AnnotatedReadonly],
        };
    }

    /**
     * @return ($kind is TypeKind::Resolved ? Type : ?Type)
     */
    public function type(TypeKind $kind = TypeKind::Resolved): ?Type
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

    private ?PropertyAdapter $native = null;

    public function toNativeReflection(): \ReflectionProperty
    {
        return $this->native ??= new PropertyAdapter($this, $this->reflector);
    }
}
