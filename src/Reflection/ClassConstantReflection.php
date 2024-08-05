<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\Misc\NonSerializable;
use Typhoon\Reflection\Internal\NativeAdapter\ClassConstantAdapter;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 * @psalm-import-type Attributes from ReflectionCollections
 */
final class ClassConstantReflection
{
    use NonSerializable;

    public readonly ClassConstantId $id;

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
        ClassConstantId $id,
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

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined] || $this->declaringClass()->isInternallyDefined();
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc]?->getText();
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    /**
     * This method returns the actual class constant's value and thus might trigger autoloading or throw errors.
     *
     * @see https://github.com/typhoon-php/typhoon/issues/64
     */
    public function evaluate(): mixed
    {
        if ($this->isEnumCase()) {
            \assert($this->id->class instanceof NamedClassId, 'Enum cannot be an anonymous class');

            return \constant($this->id->class->name . '::' . $this->id->name);
        }

        return $this->data[Data::ValueExpression]->evaluate($this->reflector);
    }

    /**
     * @deprecated since 0.4.2 in favor of evaluate()
     */
    public function value(): mixed
    {
        trigger_deprecation('typhoon/reflection', '0.4.2', 'Calling %s is deprecated in favor of %s::evaluate()', __METHOD__, self::class);

        return $this->evaluate();
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

    public function isFinal(ModifierKind $kind = ModifierKind::Resolved): bool
    {
        return match ($kind) {
            ModifierKind::Resolved => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
            ModifierKind::Native => $this->data[Data::NativeFinal],
            ModifierKind::Annotated => $this->data[Data::AnnotatedFinal],
        };
    }

    public function isEnumCase(): bool
    {
        return $this->data[Data::EnumCase];
    }

    public function isBackedEnumCase(): bool
    {
        return isset($this->data[Data::BackingValueExpression]);
    }

    public function enumBackingValue(): null|int|string
    {
        $expression = $this->data[Data::BackingValueExpression];

        if ($expression === null) {
            return null;
        }

        $value = $expression->evaluate($this->reflector);
        \assert(\is_int($value) || \is_string($value), 'Enum backing value must be int|string');

        return $value;
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

    private ?\ReflectionClassConstant $native = null;

    public function toNativeReflection(): \ReflectionClassConstant
    {
        return $this->native ??= ClassConstantAdapter::create($this, $this->reflector);
    }

    private function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->data[Data::DeclaringClassId]);
    }
}
