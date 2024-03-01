<?php

declare(strict_types=1);

namespace Typhoon\Reflection\AttributeReflection;

use Typhoon\Reflection\AttributeReflection;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Metadata\AttributeMetadata;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant T of object
 */
final class AttributeReflections
{
    /**
     * @param list<AttributeReflection> $attributes
     */
    private function __construct(
        private readonly ClassReflector $classReflector,
        private readonly array $attributes,
    ) {}

    /**
     * @param list<AttributeMetadata> $attributes
     * @param \Closure(): list<\ReflectionAttribute> $nativeAttributesFactory
     */
    public static function create(
        ClassReflector $classReflector,
        array $attributes,
        \Closure $nativeAttributesFactory,
    ): self {
        return new self(
            $classReflector,
            array_map(
                static function (AttributeMetadata $attribute) use ($classReflector, $nativeAttributesFactory): AttributeReflection {
                    if (!$classReflector->classExists($attribute->name)) {
                        throw new ClassDoesNotExist($attribute->name);
                    }

                    return new AttributeReflection($attribute->name, $attribute, $nativeAttributesFactory);
                },
                $attributes,
            ),
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
