<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 * @template TAttribute of object
 */
final class AttributeReflection
{
    public const TARGET_FUNCTION = \Attribute::TARGET_FUNCTION;
    public const TARGET_CLASS = \Attribute::TARGET_CLASS;
    public const TARGET_CLASS_CONSTANT = \Attribute::TARGET_CLASS_CONSTANT;
    public const TARGET_PROPERTY = \Attribute::TARGET_PROPERTY;
    public const TARGET_METHOD = \Attribute::TARGET_METHOD;
    public const TARGET_PARAMETER = \Attribute::TARGET_PARAMETER;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param class-string<TAttribute> $name
     * @param int<0, max> $position
     * @param self::TARGET_* $target
     * @param non-empty-list $nativeOwnerArguments
     * @param ?\ReflectionAttribute<TAttribute> $nativeReflection
     */
    public function __construct(
        private readonly string $name,
        private readonly int $position,
        private readonly int $target,
        private readonly bool $repeated,
        private readonly array $nativeOwnerArguments,
        private ?\ReflectionAttribute $nativeReflection = null,
    ) {}

    /**
     * @return class-string<TAttribute>
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return self::TARGET_*
     */
    public function getTarget(): int
    {
        return $this->target;
    }

    public function isRepeated(): bool
    {
        return $this->repeated;
    }

    public function getArguments(): array
    {
        return $this->getNativeReflection()->getArguments();
    }

    /**
     * @return TAttribute
     */
    public function newInstance(): object
    {
        return $this->getNativeReflection()->newInstance();
    }

    public function __serialize(): array
    {
        return array_diff_key(get_object_vars($this), [
            'nativeReflection' => null,
        ]);
    }

    /**
     * @return \ReflectionAttribute<TAttribute>
     */
    public function getNativeReflection(): \ReflectionAttribute
    {
        if ($this->nativeReflection !== null) {
            return $this->nativeReflection;
        }

        /** @psalm-suppress MixedArgument */
        $owner = match ($this->target) {
            self::TARGET_FUNCTION => new \ReflectionFunction(...$this->nativeOwnerArguments),
            self::TARGET_CLASS => new \ReflectionClass(...$this->nativeOwnerArguments),
            self::TARGET_CLASS_CONSTANT => new \ReflectionClassConstant(...$this->nativeOwnerArguments),
            self::TARGET_PROPERTY => new \ReflectionProperty(...$this->nativeOwnerArguments),
            self::TARGET_METHOD => new \ReflectionMethod(...$this->nativeOwnerArguments),
            self::TARGET_PARAMETER => new \ReflectionParameter(...$this->nativeOwnerArguments),
        };
        /** @var \ReflectionAttribute<TAttribute> */
        $attribute = $owner->getAttributes()[$this->position];

        return $this->nativeReflection = $attribute;
    }
}
