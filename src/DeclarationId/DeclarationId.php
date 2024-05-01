<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
abstract class DeclarationId
{
    protected function __construct() {}

    final public static function function(string $name): FunctionId
    {
        if (\function_exists($name) || self::isNameValid($name)) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return new FunctionId($name);
        }

        throw new \InvalidArgumentException(sprintf('Invalid function name %s', $name));
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

            return new AnonymousClassId(
                file: $file,
                line: $line,
                originalName: class_exists($name, autoload: false) ? $name : null,
            );
        }

        if (class_exists($name, autoload: false)
            || interface_exists($name, autoload: false)
            || trait_exists($name, autoload: false)
            || self::isNameValid($name)
        ) {
            /** @psalm-suppress ArgumentTypeCoercion */
            return new ClassId($name);
        }

        throw new \InvalidArgumentException(sprintf('Invalid class name %s', $name));
    }

    final public static function anonymousClass(string $file, int $line): AnonymousClassId
    {
        if ($file === '') {
            throw new \InvalidArgumentException('File name must not be empty');
        }

        if ($line <= 0) {
            throw new \InvalidArgumentException('Line number must not be a positive integer');
        }

        return new AnonymousClassId($file, $line);
    }

    final public static function classConstant(string|ClassId|AnonymousClassId $class, string $name): ClassConstantId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        if (!self::isLabelValid($name)) {
            throw new \InvalidArgumentException(sprintf('Invalid class constant name %s', $name));
        }

        return new ClassConstantId($class, $name);
    }

    final public static function property(string|ClassId|AnonymousClassId $class, string $name): PropertyId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        if (!self::isLabelValid($name)) {
            throw new \InvalidArgumentException(sprintf('Invalid property name %s', $name));
        }

        return new PropertyId($class, $name);
    }

    final public static function method(string|ClassId|AnonymousClassId $class, string $name): MethodId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        if (!self::isLabelValid($name)) {
            throw new \InvalidArgumentException(sprintf('Invalid method name %s', $name));
        }

        return new MethodId($class, $name);
    }

    final public static function parameter(MethodId $function, string $name): ParameterId
    {
        if (!self::isLabelValid($name)) {
            throw new \InvalidArgumentException(sprintf('Invalid parameter name %s', $name));
        }

        return new ParameterId($function, $name);
    }

    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    private static function isNameValid(string $name): bool
    {
        return preg_match('/^[a-zA-Z\x80-\xff_][a-zA-Z0-9\x80-\xff_]*(\\\[a-zA-Z\x80-\xff_][a-zA-Z0-9\x80-\xff_]*)*$/', $name) === 1;
    }

    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    private static function isLabelValid(string $name): bool
    {
        return preg_match('/^[a-zA-Z\x80-\xff_][a-zA-Z0-9\x80-\xff_]*$/', $name) === 1;
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

    abstract public function equals(self $id): bool;
}
