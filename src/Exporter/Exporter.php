<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exporter;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Exporter
{
    /**
     * @var array<class-string, list<\ReflectionProperty>>
     */
    private array $properties = [];

    private bool $hydratorAssigned = false;

    private function __construct() {}

    /**
     * @param int<0, max> $indent
     */
    public static function export(mixed $value, int $indent = 0): string
    {
        return (new self())->exportMixed($value, $indent);
    }

    /**
     * @param int<0, max> $indent
     */
    private static function indentAsString(int $indent): string
    {
        return str_repeat('    ', $indent);
    }

    /**
     * @param int<0, max> $indent
     */
    private function exportMixed(mixed $value, int $indent): string
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
            return $this->exportArray($value, $indent);
        }

        if ($value instanceof \stdClass) {
            return '(object) ' . $this->exportArray((array) $value, $indent);
        }

        if ($value instanceof \UnitEnum) {
            return var_export($value, true);
        }

        if ($value instanceof \Closure) {
            throw new \InvalidArgumentException('Cannot export Closure.');
        }

        if (\is_object($value)) {
            return $this->exportObject($value, $indent);
        }

        return var_export($value, true);
    }

    /**
     * @param int<0, max> $indent
     */
    private function exportArray(array $array, int $indent): string
    {
        if ($array === []) {
            return '[]';
        }

        $indentAsString = self::indentAsString($indent);
        $code = '[' . PHP_EOL;
        $list = array_is_list($array);

        foreach ($array as $key => $value) {
            $code .= $indentAsString . '    ';

            if (!$list) {
                $code .= var_export($key, true) . ' => ';
            }

            $code .= $this->exportMixed($value, $indent + 1) . ',' . PHP_EOL;
        }

        return $code . $indentAsString . ']';
    }

    /**
     * @param int<0, max> $indent
     */
    private function exportObject(object $object, int $indent): string
    {
        $hydrator = '$__hydrator';

        if (!$this->hydratorAssigned) {
            $this->hydratorAssigned = true;
            $hydratorClass = Hydrator::class;
            $hydrator = "(\$__hydrator ??= new \\{$hydratorClass}())";
        }

        return "{$hydrator}->hydrate({$this->exportObjectData($object, $indent)})";
    }

    /**
     * @param int<0, max> $indent
     */
    private function exportObjectData(object $object, int $indent): string
    {
        $indentAsString = self::indentAsString($indent);
        $code = '[' . PHP_EOL;

        foreach ($this->collectObjectData($object) as $class => $values) {
            $code .= $indentAsString . "    \\{$class}::class => {$this->exportArray($values, $indent + 1)}," . PHP_EOL;
        }

        return $code . $indentAsString . ']';
    }

    /**
     * @return non-empty-array<class-string, array<string, mixed>>
     */
    private function collectObjectData(object $object): array
    {
        $data = [$object::class => []];

        if (method_exists($object, '__serialize')) {
            /** @var array */
            $serializeData = $object->__serialize();

            foreach ($this->allInstanceProperties($object::class) as $property) {
                if (\array_key_exists($property->name, $serializeData)) {
                    $data[$property->class][$property->name] = $serializeData[$property->name];
                }
            }

            return $data;
        }

        $nonPrivatePropertiesMap = [];

        foreach ($this->allInstanceProperties($object::class) as $property) {
            if (!$property->isPrivate()) {
                if (isset($nonPrivatePropertiesMap[$property->name])) {
                    continue;
                }

                $nonPrivatePropertiesMap[$property->name] = true;
            }

            $data[$property->class][$property->name] = $property->getValue($object);
        }

        return $data;
    }

    /**
     * @param class-string $class
     * @return list<\ReflectionProperty>
     */
    private function allInstanceProperties(string $class): array
    {
        if (isset($this->properties[$class])) {
            return $this->properties[$class];
        }

        $reflectionClass = new \ReflectionClass($class);
        $properties = [];

        do {
            foreach ($reflectionClass->getProperties() as $property) {
                if (!$property->isStatic() && $property->class === $class) {
                    $properties[] = $property;
                }
            }

            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass !== false);

        return $this->properties[$class] = $properties;
    }
}
