<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\FunctionReflection;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\TypeKind;
use function Typhoon\Reflection\Internal\get_short_name;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class FunctionAdapter extends \ReflectionFunction
{
    public const ANONYMOUS_FUNCTION_SHORT_NAME = '{closure}';

    public function __construct(
        private readonly FunctionReflection $reflection,
    ) {
        unset($this->name);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->getName(),
            default => new \LogicException(\sprintf('Undefined property %s::$%s', self::class, $name)),
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'name';
    }

    public function __toString(): string
    {
        $this->loadNative();

        return parent::__toString();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::fromList($this->reflection->attributes(), $name, $flags);
    }

    public function getClosure(): \Closure
    {
        $this->loadNative();

        return parent::getClosure();
    }

    public function getClosureCalledClass(): ?\ReflectionClass
    {
        return null;
    }

    public function getClosureScopeClass(): ?\ReflectionClass
    {
        return null;
    }

    public function getClosureThis(): ?object
    {
        return null;
    }

    public function getClosureUsedVariables(): array
    {
        return [];
    }

    public function getDocComment(): string|false
    {
        return $this->reflection->phpDoc() ?? false;
    }

    public function getEndLine(): int|false
    {
        return $this->reflection->location()?->endLine ?? false;
    }

    public function getExtension(): ?\ReflectionExtension
    {
        $extension = $this->reflection->extension();

        if ($extension === null) {
            return null;
        }

        return new \ReflectionExtension($extension);
    }

    public function getExtensionName(): string|false
    {
        return $this->reflection->extension() ?? false;
    }

    public function getFileName(): string|false
    {
        return $this->reflection->file() ?? false;
    }

    public function getName(): string
    {
        if ($this->reflection->id instanceof NamedFunctionId) {
            return $this->reflection->id->name;
        }

        $namespace = $this->reflection->namespace();

        if ($namespace === '') {
            return self::ANONYMOUS_FUNCTION_SHORT_NAME;
        }

        return $namespace . '\\' . self::ANONYMOUS_FUNCTION_SHORT_NAME;
    }

    public function getNamespaceName(): string
    {
        return $this->reflection->namespace();
    }

    public function getNumberOfParameters(): int
    {
        return $this->reflection->parameters()->count();
    }

    public function getNumberOfRequiredParameters(): int
    {
        return $this
            ->reflection
            ->parameters()
            ->filter(static fn(ParameterReflection $reflection): bool => !$reflection->isOptional())
            ->count();
    }

    /**
     * @return list<\ReflectionParameter>
     */
    public function getParameters(): array
    {
        return $this
            ->reflection
            ->parameters()
            ->map(static fn(ParameterReflection $parameter): \ReflectionParameter => $parameter->toNativeReflection())
            ->toList();
    }

    public function getReturnType(): ?\ReflectionType
    {
        return $this->reflection->returnType(TypeKind::Native)?->accept(new ToNativeTypeConverter());
    }

    public function getShortName(): string
    {
        $id = $this->reflection->id;

        if ($id instanceof AnonymousFunctionId) {
            return self::ANONYMOUS_FUNCTION_SHORT_NAME;
        }

        $shortName = get_short_name($id->name);

        \assert($shortName !== '', 'A valid function name must not end with a backslash');

        return $shortName;
    }

    public function getStartLine(): int|false
    {
        return $this->reflection->location()?->startLine ?? false;
    }

    public function getStaticVariables(): array
    {
        $this->loadNative();

        return parent::getStaticVariables();
    }

    public function getTentativeReturnType(): ?\ReflectionType
    {
        return $this->reflection->returnType(TypeKind::Tentative)?->accept(new ToNativeTypeConverter());
    }

    public function hasReturnType(): bool
    {
        return $this->reflection->returnType(TypeKind::Native) !== null;
    }

    public function hasTentativeReturnType(): bool
    {
        return $this->reflection->returnType(TypeKind::Tentative) !== null;
    }

    public function inNamespace(): bool
    {
        return $this->reflection->namespace() !== '';
    }

    public function invoke(mixed ...$args): mixed
    {
        $this->loadNative();

        return parent::invoke(...$args);
    }

    public function invokeArgs(array $args = []): mixed
    {
        $this->loadNative();

        return parent::invokeArgs($args);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, UnusedPsalmSuppress
     */
    public function isAnonymous(): bool
    {
        return $this->reflection->isAnonymous();
    }

    public function isDisabled(): bool
    {
        $this->loadNative();

        return parent::isDisabled();
    }

    public function isClosure(): bool
    {
        return false;
    }

    public function isDeprecated(): bool
    {
        return $this->reflection->isDeprecated();
    }

    public function isGenerator(): bool
    {
        return $this->reflection->isGenerator();
    }

    public function isInternal(): bool
    {
        return $this->reflection->isInternallyDefined();
    }

    public function isStatic(): bool
    {
        return $this->reflection->isStatic();
    }

    public function isUserDefined(): bool
    {
        return !$this->isInternal();
    }

    public function isVariadic(): bool
    {
        return $this->reflection->isVariadic();
    }

    public function returnsReference(): bool
    {
        return $this->reflection->returnsReference();
    }

    private bool $nativeLoaded = false;

    private function loadNative(): void
    {
        if ($this->nativeLoaded) {
            return;
        }

        if ($this->reflection->id instanceof AnonymousFunctionId) {
            throw new \LogicException(\sprintf('Cannot natively reflect %s', $this->reflection->id->describe()));
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        parent::__construct($this->reflection->id->name);
        $this->nativeLoaded = true;
    }
}
