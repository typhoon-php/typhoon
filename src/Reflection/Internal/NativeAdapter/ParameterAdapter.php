<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\ConstantExpression\ClassConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantFetch;
use Typhoon\Reflection\Internal\ConstantExpression\MagicClassInTrait;
use Typhoon\Reflection\Internal\ConstantExpression\ParentClass;
use Typhoon\Reflection\Internal\ConstantExpression\SelfClass;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Type\IsNativeTypeNullable;
use Typhoon\Reflection\ParameterReflection;
use Typhoon\Reflection\TypeKind;
use Typhoon\Reflection\TyphoonReflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @property-read non-empty-string $name
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ParameterAdapter extends \ReflectionParameter
{
    public function __construct(
        private readonly ParameterReflection $reflection,
        private readonly TyphoonReflector $reflector,
    ) {
        unset($this->name);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'name' => $this->reflection->id->name,
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

    public function allowsNull(): bool
    {
        $nativeType = $this->reflection->type(TypeKind::Native);

        return $nativeType === null || $nativeType->accept(new IsNativeTypeNullable());
    }

    public function canBePassedByValue(): bool
    {
        return $this->reflection->canBePassedByValue();
    }

    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        return AttributeAdapter::fromList($this->reflection->attributes(), $name, $flags);
    }

    public function getClass(): ?\ReflectionClass
    {
        $this->loadNative();

        return parent::getClass();
    }

    public function getDeclaringClass(): ?\ReflectionClass
    {
        $declaringFunction = $this->getDeclaringFunction();

        if ($declaringFunction instanceof \ReflectionMethod) {
            return $declaringFunction->getDeclaringClass();
        }

        return null;
    }

    public function getDeclaringFunction(): \ReflectionFunctionAbstract
    {
        return $this->reflection->function()->toNativeReflection();
    }

    public function getDefaultValue(): mixed
    {
        return $this->reflection->evaluateDefault();
    }

    public function getDefaultValueConstantName(): ?string
    {
        $expression = $this->reflection->data[Data::DefaultValueExpression];

        if ($expression instanceof MagicClassInTrait) {
            return '__CLASS__';
        }

        if ($expression instanceof ConstantFetch) {
            return $expression->name($this->reflector);
        }

        if ($expression instanceof ClassConstantFetch) {
            $name = $expression->evaluateName($this->reflector);

            if ($name === 'class') {
                return null;
            }

            if ($expression->class instanceof SelfClass) {
                return 'self::' . $name;
            }

            if ($expression->class instanceof ParentClass) {
                return 'parent::' . $name;
            }

            return $expression->evaluateClass($this->reflector) . '::' . $name;
        }

        return null;
    }

    public function getName(): string
    {
        return $this->reflection->id->name;
    }

    public function getPosition(): int
    {
        return $this->reflection->index();
    }

    public function getType(): ?\ReflectionType
    {
        return $this->reflection->type(TypeKind::Native)?->accept(new ToNativeTypeConverter());
    }

    public function hasType(): bool
    {
        return $this->reflection->type(TypeKind::Native) !== null;
    }

    public function isArray(): bool
    {
        $this->loadNative();

        return parent::isArray();
    }

    public function isCallable(): bool
    {
        $this->loadNative();

        return parent::isCallable();
    }

    public function isDefaultValueAvailable(): bool
    {
        return $this->reflection->hasDefaultValue();
    }

    public function isDefaultValueConstant(): bool
    {
        $expression = $this->reflection->data[Data::DefaultValueExpression];

        return $expression instanceof ConstantFetch
            || $expression instanceof MagicClassInTrait
            || ($expression instanceof ClassConstantFetch && $expression->evaluateName($this->reflector) !== 'class');
    }

    public function isOptional(): bool
    {
        return $this->reflection->isOptional();
    }

    public function isPassedByReference(): bool
    {
        return $this->reflection->canBePassedByReference();
    }

    public function isPromoted(): bool
    {
        return $this->reflection->isPromoted();
    }

    public function isVariadic(): bool
    {
        return $this->reflection->isVariadic();
    }

    private bool $nativeLoaded = false;

    private function loadNative(): void
    {
        if ($this->nativeLoaded) {
            return;
        }

        $functionId = $this->reflection->id->function;

        if ($functionId instanceof NamedFunctionId) {
            parent::__construct($functionId->name, $this->name);
            $this->nativeLoaded = true;

            return;
        }

        if ($functionId instanceof AnonymousFunctionId) {
            throw new \LogicException(\sprintf('Cannot natively reflect %s', $functionId->describe()));
        }

        $class = $functionId->class->name ?? throw new \LogicException(\sprintf(
            "Cannot natively reflect %s, because it's runtime name is not available",
            $functionId->class->describe(),
        ));

        parent::__construct([$class, $functionId->name], $this->name);
        $this->nativeLoaded = true;
    }
}
