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
    private function __construct(
        string $name,
        private readonly bool $builtIn = true,
        private readonly bool $nullable = false,
    ) {
        $this->_name = $name;
    }

    public static function null(): self
    {
        return new self('null', nullable: true);
    }

    public static function true(): self
    {
        return new self('true');
    }

    public static function false(): self
    {
        return new self('false');
    }

    public static function bool(): self
    {
        return new self('bool');
    }

    public static function int(): self
    {
        return new self('int');
    }

    public static function float(): self
    {
        return new self('float');
    }

    public static function string(): self
    {
        return new self('string');
    }

    public static function array(): self
    {
        return new self('array');
    }

    public static function object(): self
    {
        return new self('object');
    }

    public static function iterable(): self
    {
        return new self('iterable');
    }

    public static function callable(): self
    {
        return new self('callable');
    }

    public static function mixed(): self
    {
        return new self('mixed', nullable: true);
    }

    public static function void(): self
    {
        return new self('void');
    }

    public static function never(): self
    {
        return new self('never');
    }

    /**
     * @param non-empty-string $name
     */
    public static function namedObject(string $name): self
    {
        return new self($name, builtIn: false);
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
