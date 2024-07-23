<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\MethodId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\NativeAdapter\MethodAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

/**
 * @api
 */
final class MethodReflection
{
    public readonly MethodId $id;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @var ?NameMap<TemplateReflection>
     */
    private ?NameMap $templates = null;

    /**
     * @var ?ListOf<AttributeReflection>
     */
    private ?ListOf $attributes = null;

    /**
     * @var ?NameMap<ParameterReflection>
     */
    private ?NameMap $parameters;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        MethodId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * @return TemplateReflection[]
     * @psalm-return NameMap<TemplateReflection>
     * @phpstan-return NameMap<TemplateReflection>
     */
    public function templates(): NameMap
    {
        return $this->templates ??= (new NameMap($this->data[Data::Templates]))->map(
            fn(TypedMap $data, string $name): TemplateReflection => new TemplateReflection(Id::template($this->id, $name), $data),
        );
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
     * @return ParameterReflection[]
     * @psalm-return NameMap<ParameterReflection>
     * @phpstan-return NameMap<ParameterReflection>
     */
    public function parameters(): NameMap
    {
        return $this->parameters ??= (new NameMap($this->data[Data::Parameters]))->map(
            fn(TypedMap $data, string $name): ParameterReflection => new ParameterReflection(Id::parameter($this->id, $name), $data, $this->reflector),
        );
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
     * @return ?non-empty-string
     */
    public function file(): ?string
    {
        if ($this->data[Data::InternallyDefined]) {
            return null;
        }

        return $this->declaringClass()->file();
    }

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    public function isNative(): bool
    {
        return !$this->isAnnotated();
    }

    public function isAnnotated(): bool
    {
        return $this->data[Data::Annotated];
    }

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined] || $this->declaringClass()->isInternallyDefined();
    }

    public function isAbstract(): bool
    {
        return $this->data[Data::Abstract];
    }

    public function isFinal(?DeclarationKind $kind = null): bool
    {
        return match ($kind) {
            DeclarationKind::Native => $this->data[Data::NativeFinal],
            DeclarationKind::Annotated => $this->data[Data::AnnotatedFinal],
            null => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isGenerator(): bool
    {
        return $this->data[Data::Generator];
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

    public function isStatic(): bool
    {
        return $this->data[Data::Static];
    }

    public function isVariadic(): bool
    {
        $lastParameterName = array_key_last($this->parameters()->names());

        return $lastParameterName !== null && $this->parameters()[$lastParameterName]->isVariadic();
    }

    public function returnsReference(): bool
    {
        return $this->data[Data::ByReference];
    }

    /**
     * @return ($kind is null ? Type : ?Type)
     */
    public function returnType(?DeclarationKind $kind = null): ?Type
    {
        return $this->data[Data::Type]->get($kind);
    }

    public function throwsType(): ?Type
    {
        return $this->data[Data::ThrowsType];
    }

    public function isDeprecated(): bool
    {
        return $this->data[Data::Deprecation] !== null;
    }

    public function deprecation(): ?Deprecation
    {
        return $this->data[Data::Deprecation];
    }

    private ?MethodAdapter $native = null;

    public function toNativeReflection(): \ReflectionMethod
    {
        return $this->native ??= new MethodAdapter($this, $this->reflector);
    }

    private function declaringClass(): ClassReflection
    {
        return $this->reflector->reflect($this->data[Data::DeclaringClassId]);
    }
}
