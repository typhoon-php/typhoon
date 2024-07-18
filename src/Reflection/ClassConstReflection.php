<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\ClassConstId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\NativeAdapter\ClassConstantAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

/**
 * @api
 */
final class ClassConstReflection
{
    public readonly ClassConstId $id;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @var ?ListOf<AttributeReflection>
     */
    private ?ListOf $attributes = null;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        ClassConstId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * @return AttributeReflection[]
     * @psalm-return ListOf<AttributeReflection>
     * @phpstan-return ListOf<AttributeReflection>
     */
    public function attributes(): ListOf
    {
        return $this->attributes ??= (new ListOf($this->data[Data::Attributes]))->map(
            fn(TypedMap $data, int $index): AttributeReflection => new AttributeReflection($this->id, $index, $data, $this->reflector),
        );
    }

    /**
     * @return ?positive-int
     */
    public function startLine(): ?int
    {
        return $this->data[Data::StartLine];
    }

    /**
     * @return ?positive-int
     */
    public function endLine(): ?int
    {
        return $this->data[Data::EndLine];
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
        return $this->data[Data::PhpDoc];
    }

    public function class(): ClassReflection
    {
        return $this->reflector->reflect($this->id->class);
    }

    public function value(): mixed
    {
        if ($this->isEnumCase()) {
            \assert($this->id->class instanceof NamedClassId, 'Enum cannot be an anonymous class');

            return \constant($this->id->class->name . '::' . $this->id->name);
        }

        return $this->data[Data::ValueExpression]->evaluate($this->reflector);
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

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal],
            Kind::Annotated => $this->data[Data::AnnotatedFinal],
            Kind::Resolved => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isEnumCase(): bool
    {
        return $this->data[Data::EnumCase];
    }

    public function isBackedEnumCase(): bool
    {
        return isset($this->data[Data::EnumBackingValueExpression]);
    }

    public function enumBackingValue(): null|int|string
    {
        $expression = $this->data[Data::EnumBackingValueExpression];

        if ($expression === null) {
            return null;
        }

        $value = $expression->evaluate($this->reflector);
        \assert(\is_int($value) || \is_string($value), 'Enum backing value must be int|string');

        return $value;
    }

    /**
     * @return ($kind is Kind::Resolved ? Type : ?Type)
     */
    public function type(Kind $kind = Kind::Resolved): ?Type
    {
        return $this->data[Data::Type]->get($kind);
    }

    private ?\ReflectionClassConstant $native = null;

    public function native(): \ReflectionClassConstant
    {
        return $this->native ??= ClassConstantAdapter::create($this, $this->reflector);
    }

    private function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->data[Data::DeclaringClassId]);
    }
}
