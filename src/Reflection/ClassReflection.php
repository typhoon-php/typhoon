<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\TemplateTypeResolver;

/**
 * @api
 * @template-covariant TObject of object
 * @template-covariant TId of NamedClassId<class-string<TObject>>|AnonymousClassId<?class-string<TObject>>
 */
final class ClassReflection
{
    /**
     * @var TId
     */
    public readonly AnonymousClassId|NamedClassId $id;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @var ?NameMap<AliasReflection>
     */
    private ?NameMap $aliases = null;

    /**
     * @var ?NameMap<TemplateReflection>
     */
    private ?NameMap $templates = null;

    /**
     * @var ?ListOf<AttributeReflection>
     */
    private ?ListOf $attributes = null;

    /**
     * @var ?NameMap<ClassConstantReflection>
     */
    private ?NameMap $constants = null;

    /**
     * @var ?NameMap<PropertyReflection>
     */
    private ?NameMap $properties = null;

    /**
     * @var ?NameMap<MethodReflection>
     */
    private ?NameMap $methods = null;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param TId $id
     */
    public function __construct(
        NamedClassId|AnonymousClassId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * @return AliasReflection[]
     * @psalm-return NameMap<AliasReflection>
     * @phpstan-return NameMap<AliasReflection>
     */
    public function aliases(): NameMap
    {
        return $this->aliases ??= (new NameMap($this->data[Data::Aliases]))->map(
            fn(TypedMap $data, string $name): AliasReflection => new AliasReflection(Id::alias($this->id, $name), $data),
        );
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
     * @return ClassConstantReflection[]
     * @psalm-return NameMap<ClassConstantReflection>
     * @phpstan-return NameMap<ClassConstantReflection>
     */
    public function enumCases(): NameMap
    {
        return $this->constants()->filter(static fn(ClassConstantReflection $reflection): bool => $reflection->isEnumCase());
    }

    /**
     * @return ClassConstantReflection[]
     * @psalm-return NameMap<ClassConstantReflection>
     * @phpstan-return NameMap<ClassConstantReflection>
     */
    public function constants(): NameMap
    {
        return $this->constants ??= (new NameMap($this->data[Data::Constants]))->map(
            fn(TypedMap $data, string $name): ClassConstantReflection => new ClassConstantReflection(Id::classConstant($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return PropertyReflection[]
     * @psalm-return NameMap<PropertyReflection>
     * @phpstan-return NameMap<PropertyReflection>
     */
    public function properties(): NameMap
    {
        return $this->properties ??= (new NameMap($this->data[Data::Properties]))->map(
            fn(TypedMap $data, string $name): PropertyReflection => new PropertyReflection(Id::property($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return MethodReflection[]
     * @psalm-return NameMap<MethodReflection>
     * @phpstan-return NameMap<MethodReflection>
     */
    public function methods(): NameMap
    {
        return $this->methods ??= (new NameMap($this->data[Data::Methods]))->map(
            fn(TypedMap $data, string $name): MethodReflection => new MethodReflection(Id::method($this->id, $name), $data, $this->reflector),
        );
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc]?->getText();
    }

    public function changeDetector(): ChangeDetector
    {
        return $this->data[Data::ChangeDetector];
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     */
    public function isInstanceOf(string|NamedClassId|AnonymousClassId $class): bool
    {
        if (\is_string($class)) {
            $class = Id::class($class);
        }

        if ($this->id->equals($class)) {
            return true;
        }

        if ($class instanceof AnonymousClassId) {
            return false;
        }

        return \array_key_exists($class->name, $this->data[Data::Parents])
            || \array_key_exists($class->name, $this->data[Data::Interfaces]);
    }

    public function isClass(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Class_;
    }

    /**
     * This method is different from {@see \ReflectionClass::isAbstract()}. It returns true only for explicitly
     * abstract classes:
     *     abstract class A {} -> true
     *     class C {} -> false
     *     interface I { public function m() {} } -> false
     *     trait T { abstract public function m() {} } -> false.
     */
    public function isAbstract(): bool
    {
        return $this->data[Data::Abstract];
    }

    public function isAnonymous(): bool
    {
        return $this->id instanceof AnonymousClassId;
    }

    public function isInterface(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Interface;
    }

    public function isTrait(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Trait;
    }

    public function isEnum(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Enum;
    }

    public function isBackedEnum(): bool
    {
        return $this->data[Data::BackingType] !== null;
    }

    /**
     * @return (TObject is \BackedEnum ? Type<int|string> : ?Type<int|string>)
     */
    public function enumBackingType(): ?Type
    {
        return $this->data[Data::BackingType];
    }

    public function isFinal(?DeclarationKind $kind = null): bool
    {
        return match ($kind) {
            DeclarationKind::Native => $this->data[Data::NativeFinal],
            DeclarationKind::Annotated => $this->data[Data::AnnotatedFinal],
            null => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isReadonly(?DeclarationKind $kind = null): bool
    {
        return match ($kind) {
            DeclarationKind::Native => $this->data[Data::NativeReadonly],
            DeclarationKind::Annotated => $this->data[Data::AnnotatedReadonly],
            null => $this->data[Data::NativeReadonly] || $this->data[Data::AnnotatedReadonly],
        };
    }

    /**
     * Unlike {@see \ReflectionClass::getNamespaceName()}, this method returns the actual namespace
     * an anonymous class is defined in.
     */
    public function namespace(): string
    {
        return $this->data[Data::Namespace];
    }

    public function parent(): ?self
    {
        $parentName = $this->parentName();

        if ($parentName === null) {
            return null;
        }

        return $this->reflector->reflect(Id::namedClass($parentName));
    }

    /**
     * @return ?class-string
     */
    public function parentName(): ?string
    {
        return array_key_first($this->data[Data::Parents]);
    }

    /**
     * @return ?non-empty-string
     */
    public function extension(): ?string
    {
        return $this->data[Data::PhpExtension];
    }

    /**
     * @return ?non-empty-string
     */
    public function file(): ?string
    {
        return $this->data[Data::File];
    }

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined];
    }

    /**
     * @param list<Type> $typeArguments
     */
    public function createTemplateResolver(array $typeArguments): TemplateTypeResolver
    {
        return new TemplateTypeResolver(
            $this
                ->templates()
                ->map(static fn(TemplateReflection $template): array => [
                    $template->id,
                    $typeArguments[$template->index()] ?? $template->constraint(),
                ]),
        );
    }

    public function isDeprecated(): bool
    {
        return $this->data[Data::Deprecation] !== null;
    }

    public function deprecation(): ?Deprecation
    {
        return $this->data[Data::Deprecation];
    }

    /**
     * @var ?\ReflectionClass<TObject>
     */
    private ?\ReflectionClass $native = null;

    /**
     * @return \ReflectionClass<TObject>
     */
    public function toNativeReflection(): \ReflectionClass
    {
        return $this->native ??= ClassAdapter::create($this, $this->reflector);
    }
}
