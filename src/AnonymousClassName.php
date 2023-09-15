<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Node\Stmt\Class_;
use Typhoon\Reflection\NameResolution\NameContext;

/**
 * @api
 * @psalm-immutable
 */
final class AnonymousClassName
{
    /**
     * @param non-empty-string $file
     * @param int<0, max> $line
     * @param ?class-string $superType
     * @param ?int<0, max> $rtdKeyCounter
     */
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly ?string $superType = null,
        public readonly ?int $rtdKeyCounter = null,
    ) {}

    /**
     * @param non-empty-string $file
     */
    public static function fromNode(string $file, Class_ $node, NameContext $nameContext): self
    {
        $line = $node->getStartLine();

        if ($line < 0) {
            throw new ReflectionException();
        }

        return new self(file: $file, line: $line, superType: self::resolveSuperType($node, $nameContext));
    }

    /**
     * @psalm-pure
     */
    public static function tryFromString(string $name): ?self
    {
        if (!str_contains($name, '@')) {
            return null;
        }

        if (preg_match('/^(.+)@anonymous\x00(.+):(\d+)(?:\$(\w+))?$/', $name, $matches) !== 1) {
            return null;
        }

        /** @var ?class-string */
        $superType = $matches[1] === 'class' ? null : $matches[1];
        /** @var non-empty-string */
        $file = $matches[2];
        /** @var int<0, max> */
        $line = (int) $matches[3];
        /** @var ?int<0, max> */
        $rtdKeyCounter = isset($matches[4]) ? hexdec($matches[4]) : null;

        return new self(file: $file, line: $line, superType: $superType, rtdKeyCounter: $rtdKeyCounter);
    }

    /**
     * @return list<self>
     */
    public static function declared(?string $file = null, ?int $line = null): array
    {
        $names = [];

        foreach (get_declared_classes() as $class) {
            $name = self::tryFromString($class);

            if ($name === null) {
                continue;
            }

            if ($file !== null && $name->file !== $file) {
                continue;
            }

            if ($line !== null && $name->line !== $line) {
                continue;
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * @return ?class-string
     */
    private static function resolveSuperType(Class_ $node, NameContext $nameContext): ?string
    {
        if ($node->extends !== null) {
            return $nameContext->resolveNameAsClass($node->extends->toCodeString());
        }

        foreach ($node->implements as $interface) {
            return $nameContext->resolveNameAsClass($interface->toCodeString());
        }

        return null;
    }

    /**
     * @return class-string this is not actually true, but it's easier to put it that way
     */
    public function toStringWithoutRtdKeyCounter(): string
    {
        /** @var class-string */
        return sprintf("%s@anonymous\x00%s:%d", $this->superType ?? 'class', $this->file, $this->line);
    }

    /**
     * @return class-string
     */
    public function toString(): string
    {
        if ($this->rtdKeyCounter === null) {
            throw new ReflectionException();
        }

        /** @var class-string */
        return $this->toStringWithoutRtdKeyCounter() . '$' . dechex($this->rtdKeyCounter);
    }
}
