<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Exporter;

final class Exporter
{
    /**
     * @var array<class-string, \Closure(array): object>
     */
    private static array $hydrators = [];

    private function __construct()
    {
    }

    /**
     * @param 0|positive-int $indent
     */
    public static function export(mixed $value, int $indent = 0): string
    {
        if ($value === null) {
            return 'null';
        }

        if (\is_scalar($value)) {
            return var_export($value, true);
        }

        if (\is_resource($value)) {
            throw new \InvalidArgumentException('Cannot export resource.');
        }

        if (\is_array($value)) {
            return self::exportArray($value, $indent);
        }

        if ($value instanceof \stdClass) {
            return '(object) ' . self::exportArray((array) $value, $indent);
        }

        if ($value instanceof \UnitEnum) {
            return var_export($value, true);
        }

        if ($value instanceof \Closure) {
            throw new \InvalidArgumentException('Cannot export Closure.');
        }

        if (\is_object($value)) {
            return sprintf(
                '\%s::hydrate(\%s::class, %s)',
                self::class,
                $value::class,
                self::export(self::extractObjectData($value), $indent),
            );
        }

        return var_export($value, true);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public static function hydrate(string $class, array $data): object
    {
        /** @var T */
        return (self::$hydrators[$class] ??= self::createHydrator($class))($data);
    }

    /**
     * @param 0|positive-int $indent
     */
    private static function exportArray(array $array, int $indent): string
    {
        if ($array === []) {
            return '[]';
        }

        $nextIndentAsString = self::indentAsString($indent + 1);
        $code = '[' . PHP_EOL;
        $list = array_is_list($array);

        foreach ($array as $key => $value) {
            $code .= $nextIndentAsString;

            if (!$list) {
                $code .= var_export($key, true) . ' => ';
            }

            $code .= self::export($value, $indent + 1) . ',' . PHP_EOL;
        }

        return $code . self::indentAsString($indent) . ']';
    }

    /**
     * @param 0|positive-int $indent
     */
    private static function indentAsString(int $indent): string
    {
        return str_repeat('    ', $indent);
    }

    private static function extractObjectData(object $object): array
    {
        if (method_exists($object, '__serialize')) {
            /** @var array */
            return $object->__serialize();
        }

        /** @var array */
        return (fn () => get_object_vars($this))->call($object);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return \Closure(array): T
     */
    private static function createHydrator(string $class): \Closure
    {
        $reflectionClass = new \ReflectionClass($class);
        $prototype = $reflectionClass->newInstanceWithoutConstructor();

        if ($reflectionClass->hasMethod('__unserialize')) {
            /** @var \Closure(array): T */
            return function (array $data) use ($prototype): object {
                $object = clone $prototype;
                /** @psalm-suppress MixedMethodCall */
                $object->__unserialize($data);

                return $object;
            };
        }

        $closure = (function (array $data) use ($prototype): object {
            $object = clone $prototype;

            foreach ($data as $property => $value) {
                $object->{$property} = $value;
            }

            return $object;
        })->bindTo(null, $class);

        if (!$closure) {
            throw new \RuntimeException();
        }

        /** @var \Closure(array): T */
        return $closure;
    }
}
