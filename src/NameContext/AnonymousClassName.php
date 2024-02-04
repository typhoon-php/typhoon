<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @template TObject of object
 * @psalm-suppress PossiblyUnusedProperty
 */
final class AnonymousClassName
{
    /**
     * @param class-string<TObject> $name
     * @param non-empty-string $file
     * @param int<0, max> $line
     * @param ?class-string $superType
     * @param int<0, max> $rtdKeyCounter
     */
    private function __construct(
        public readonly string $name,
        public readonly string $file,
        public readonly int $line,
        public readonly ?string $superType,
        public readonly int $rtdKeyCounter,
    ) {}

    /**
     * @template TNewObject of object
     * @param string|class-string<TNewObject> $name
     * @return ($name is class-string ? null|self<TNewObject> : null|self)
     */
    public static function tryFromString(string $name): ?self
    {
        if (!str_contains($name, '@')) {
            return null;
        }

        if (preg_match('/^\\\?(.+)@anonymous\x00(.+):(\d+)\$(\w+)$/', $name, $matches) !== 1) {
            return null;
        }

        /** @var ?class-string */
        $superType = $matches[1] === 'class' ? null : $matches[1];
        /** @var non-empty-string */
        $file = $matches[2];
        /** @var int<0, max> */
        $line = (int) $matches[3];
        /** @var int<0, max> */
        $rtdKeyCounter = hexdec($matches[4]);

        /** @var class-string $name */
        return new self($name, $file, $line, $superType, $rtdKeyCounter);
    }

    /**
     * @return list<self>
     */
    public static function findDeclared(?string $file = null, ?int $line = null): array
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
}
