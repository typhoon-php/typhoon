<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Attributes;

use Typhoon\Reflection\AttributeReflection;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Metadata\AttributeMetadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant T of object
 * @psalm-suppress PossiblyUnusedProperty
 */
final class AttributeReflections
{
    /**
     * @var list<AttributeReflection>
     */
    private readonly array $attributes;

    /**
     * @param list<AttributeMetadata> $attributes
     * @param \Closure(): list<\ReflectionAttribute> $nativeAttributes
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
        array $attributes,
        \Closure $nativeAttributes,
    ) {
        $this->attributes = array_map(
            static fn(AttributeMetadata $attribute): \ReflectionAttribute => new AttributeReflection($attribute, $nativeAttributes),
            $attributes,
        );
    }

    /**
     * @template TClass as object
     * @param class-string<TClass>|null $name
     * @return ($name is null ? list<AttributeReflection<object>> : list<AttributeReflection<TClass>>)
     */
    public function get(?string $name, int $flags): array
    {
        if ($this->attributes === []) {
            return [];
        }

        if ($name === null) {
            return $this->attributes;
        }

        if ($flags & \ReflectionAttribute::IS_INSTANCEOF) {
            /** @var list<AttributeReflection<TClass>> */
            return array_filter(
                $this->attributes,
                fn(AttributeReflection $attribute): bool => $attribute->getName() === $name
                    || $this->classReflector->reflectClass($attribute->getName())->isSubclassOf($name),
            );
        }

        /** @var list<AttributeReflection<TClass>> */
        return array_filter(
            $this->attributes,
            static fn(AttributeReflection $attribute): bool => $attribute->getName() === $name,
        );
    }
}
