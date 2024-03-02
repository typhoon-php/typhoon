<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NamedTypeReflection extends \ReflectionNamedType
{
    /**
     * @var non-empty-string
     */
    private readonly string $_name;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        string $name,
        private readonly bool $builtIn = true,
        private readonly bool $nullable = false,
    ) {
        $this->_name = $name;
    }

    public function toNullable(): self
    {
        return new self($this->_name, $this->builtIn, true);
    }

    public function allowsNull(): bool
    {
        return $this->nullable;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function isBuiltin(): bool
    {
        return $this->builtIn;
    }
}
