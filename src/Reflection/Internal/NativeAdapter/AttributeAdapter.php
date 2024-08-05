<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeAdapter;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\AttributeReflection;
use Typhoon\Reflection\Collection;
use Typhoon\Reflection\ReflectionCollections;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template TAttribute of object
 * @extends \ReflectionAttribute<TAttribute>
 * @psalm-import-type Attributes from ReflectionCollections
 */
final class AttributeAdapter extends \ReflectionAttribute
{
    public function __construct(
        private readonly AttributeReflection $reflection,
    ) {}

    /**
     * @param ?non-empty-string $name
     * @param Attributes $attributes
     * @return list<\ReflectionAttribute>
     */
    public static function fromList(Collection $attributes, ?string $name, int $flags): array
    {
        if ($name !== null) {
            if ($flags & \ReflectionAttribute::IS_INSTANCEOF) {
                $attributes = $attributes->filter(static fn(AttributeReflection $attribute): bool => $attribute->class()->isInstanceOf($name));
            } else {
                $attributes = $attributes->filter(static fn(AttributeReflection $attribute): bool => $attribute->className() === $name);
            }
        }

        return $attributes
            ->map(static fn(AttributeReflection $attribute): \ReflectionAttribute => $attribute->toNativeReflection())
            ->toList();
    }

    public function __toString(): string
    {
        $targetId = $this->reflection->targetId();

        if ($targetId instanceof AnonymousFunctionId) {
            throw new \LogicException('Cannot resolve string representation of anonymous function');
        }

        return (string) $targetId->reflect()->getAttributes()[$this->reflection->index()];
    }

    public function getArguments(): array
    {
        return $this->reflection->evaluateArguments();
    }

    public function getName(): string
    {
        return $this->reflection->className();
    }

    public function getTarget(): int
    {
        /** @psalm-suppress ParadoxicalCondition */
        return match ($this->reflection->targetId()::class) {
            NamedFunctionId::class, AnonymousFunctionId::class => \Attribute::TARGET_FUNCTION,
            NamedClassId::class, AnonymousClassId::class => \Attribute::TARGET_CLASS,
            ClassConstantId::class => \Attribute::TARGET_CLASS_CONSTANT,
            PropertyId::class => \Attribute::TARGET_PROPERTY,
            MethodId::class => \Attribute::TARGET_METHOD,
            ParameterId::class => \Attribute::TARGET_PARAMETER,
        };
    }

    public function isRepeated(): bool
    {
        return $this->reflection->isRepeated();
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public function newInstance(): object
    {
        return $this->reflection->evaluate();
    }
}
