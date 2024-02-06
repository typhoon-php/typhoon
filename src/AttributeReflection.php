<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Metadata\AttributeMetadata;

/**
 * @api
 * @template TAttribute of object
 * @extends \ReflectionAttribute<TAttribute>
 * @psalm-suppress MissingImmutableAnnotation
 */
final class AttributeReflection extends \ReflectionAttribute
{
    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param \Closure(): list<\ReflectionAttribute> $nativeAttributes
     */
    public function __construct(
        private readonly AttributeMetadata $metadata,
        private readonly \Closure $nativeAttributes,
    ) {}

    public function __toString(): string
    {
        return $this->native()->__toString();
    }

    public function getArguments(): array
    {
        return $this->native()->getArguments();
    }

    public function getName(): string
    {
        return $this->metadata->name;
    }

    public function getTarget(): int
    {
        return $this->metadata->target;
    }

    public function isRepeated(): bool
    {
        return $this->metadata->repeated;
    }

    /**
     * @return TAttribute
     */
    public function newInstance(): object
    {
        return $this->native()->newInstance();
    }

    /**
     * @return \ReflectionAttribute<TAttribute>
     */
    private function native(): \ReflectionAttribute
    {
        /** @var \ReflectionAttribute<TAttribute> */
        return ($this->nativeAttributes)()[$this->metadata->position] ?? throw new ReflectionException();
    }
}
