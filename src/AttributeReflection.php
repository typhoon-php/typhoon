<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Exception\DefaultReflectionException;
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
     * @param class-string<TAttribute> $resolvedName
     * @param \Closure(): list<\ReflectionAttribute> $nativeAttributesFactory
     */
    public function __construct(
        private readonly string $resolvedName,
        private readonly AttributeMetadata $metadata,
        private readonly \Closure $nativeAttributesFactory,
    ) {}

    public function __toString(): string
    {
        return $this->native()->__toString();
    }

    public function getArguments(): array
    {
        return $this->native()->getArguments();
    }

    /**
     * @return class-string<TAttribute>
     */
    public function getName(): string
    {
        return $this->resolvedName;
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
        return ($this->nativeAttributesFactory)()[$this->metadata->position] ?? throw new DefaultReflectionException();
    }
}
