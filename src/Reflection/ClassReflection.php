<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\NativeAdapter\ClassAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;

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
     * @var ?NameMap<ClassConstReflection>
     */
    private ?NameMap $consts = null;

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

    public function kind(): ClassKind
    {
        return $this->data[Data::ClassKind];
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
     * @return ClassConstReflection[]
     * @psalm-return NameMap<ClassConstReflection>
     * @phpstan-return NameMap<ClassConstReflection>
     */
    public function enumCases(): NameMap
    {
        return $this->consts()->filter(static fn(ClassConstReflection $reflection): bool => $reflection->isEnumCase());
    }

    /**
     * @return ClassConstReflection[]
     * @psalm-return NameMap<ClassConstReflection>
     * @phpstan-return NameMap<ClassConstReflection>
     */
    public function consts(): NameMap
    {
        return $this->consts ??= (new NameMap($this->data[Data::ClassConsts]))->map(
            fn(TypedMap $data, string $name): ClassConstReflection => new ClassConstReflection(Id::classConst($this->id, $name), $data, $this->reflector),
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
        return $this->data[Data::PhpDoc];
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

    /**
     * This method is different from {@see \ReflectionClass::isAbstract()}.
     * It returns true only for explicitly abstract classes:
     *     abstract class AC {} -> true
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
        return $this->data[Data::EnumBackingType] !== null;
    }

    /**
     * @return (TObject is \BackedEnum ? Type<int|string> : ?Type<int|string>)
     */
    public function enumBackingType(): ?Type
    {
        return $this->data[Data::EnumBackingType];
    }

    public function isFinal(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeFinal],
            Kind::Annotated => $this->data[Data::AnnotatedFinal],
            Kind::Resolved => $this->data[Data::NativeFinal] || $this->data[Data::AnnotatedFinal],
        };
    }

    public function isInterface(): bool
    {
        return $this->data[Data::ClassKind] === ClassKind::Interface;
    }

    public function isReadonly(Kind $kind = Kind::Resolved): bool
    {
        return match ($kind) {
            Kind::Native => $this->data[Data::NativeReadonly],
            Kind::Annotated => $this->data[Data::AnnotatedReadonly],
            Kind::Resolved => $this->data[Data::NativeReadonly] || $this->data[Data::AnnotatedReadonly],
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
        return $this->data[Data::InternallyDefined];
    }

    /**
     * @var ?\ReflectionClass<TObject>
     */
    private ?\ReflectionClass $native = null;

    /**
     * @return \ReflectionClass<TObject>
     */
    public function native(): \ReflectionClass
    {
        return $this->native ??= ClassAdapter::create($this, $this->reflector);
    }
}
