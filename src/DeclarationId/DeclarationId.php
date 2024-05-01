<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
abstract class DeclarationId
{
    private const NAME_REGEX = '/^[a-zA-Z0-9\x80-\xff_]+$/';

    protected function __construct() {}

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     */
    public static function anonymousClass(string $file, int $line): AnonymousClassId
    {
        return new AnonymousClassId($file, $line);
    }

    final public static function class(string $name): ClassId|AnonymousClassId
    {
        if (str_contains($name, '@')) {
            if (preg_match('/@anonymous\x00(.+):(\d+)/', $name, $matches) !== 1) {
                throw new \InvalidArgumentException(sprintf('Invalid class name %s', $name));
            }

            /** @var non-empty-string */
            $file = $matches[1];
            $line = (int) $matches[2];
            \assert($line > 0);

            return new AnonymousClassId($file, $line, class_exists($name, autoload: false) ? $name : null);
        }

        // simplified fast regex
        if (preg_match('/^[a-zA-Z0-9\x80-\xff_\\\]+$/', $name) === 1) {
            /** @var non-empty-string */
            $name = ltrim($name, '\\');

            return new ClassId($name);
        }

        throw new \InvalidArgumentException(sprintf('Invalid class name %s', $name));
    }

    final public static function classConstant(string|ClassId|AnonymousClassId $class, string $name): ClassConstantId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        if (preg_match(self::NAME_REGEX, $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid class constant name %s', $name));
        }

        /** @var non-empty-string $name */
        return new ClassConstantId($class, $name);
    }

    final public static function property(string|ClassId|AnonymousClassId $class, string $name): PropertyId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        if (preg_match(self::NAME_REGEX, $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid property name %s', $name));
        }

        /** @var non-empty-string $name */
        return new PropertyId($class, $name);
    }

    final public static function method(string|ClassId|AnonymousClassId $class, string $name): MethodId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        if (preg_match(self::NAME_REGEX, $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid method name %s', $name));
        }

        /** @var non-empty-string $name */
        return new MethodId($class, $name);
    }

    final public static function parameter(MethodId $function, string $name): ParameterId
    {
        if (preg_match(self::NAME_REGEX, $name) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid parameter name %s', $name));
        }

        /** @var non-empty-string $name */
        return new ParameterId($function, $name);
    }

    /**
     * @return non-empty-string
     */
    final public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return non-empty-string
     */
    abstract public function toString(): string;
}
