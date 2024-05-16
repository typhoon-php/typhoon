<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 */
final class Hasher
{
    private static ?self $instance = null;

    private bool $locked = false;

    /**
     * @var array<non-empty-string, false|callable>
     */
    private array $objectNormalizers;

    /**
     * @var \WeakMap<object, string>
     */
    private \WeakMap $objectHashes;

    private function __construct()
    {
        $this->objectNormalizers = [
            \DateTimeInterface::class => static fn(\DateTimeInterface $object): string => $object->format('c.u'),
            \UnitEnum::class => static fn(\UnitEnum $object): string => $object->name,
            \JsonSerializable::class => static fn(\JsonSerializable $object): mixed => $object->jsonSerialize(),
        ];
        /** @var \WeakMap<object, string> */
        $this->objectHashes = new \WeakMap();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T): mixed $normalizer
     */
    public static function registerObjectNormalizer(string $class, callable $normalizer): void
    {
        $instance = self::instance();

        if ($instance->locked) {
            throw new \LogicException(sprintf('Please register all normalizers before using %s', self::class));
        }

        $instance->objectNormalizers[$class] = $normalizer;
    }

    public static function hash(mixed $value): string
    {
        $instance = self::instance();
        $instance->locked = true;

        return $instance->hashValue($value);
    }

    private static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function hashValue(mixed $value): string
    {
        if ($value === null) {
            return 'n';
        }

        if ($value === true) {
            return 't';
        }

        if ($value === false) {
            return 'f';
        }

        if (\is_int($value)) {
            return 'i' . $value;
        }

        if (\is_float($value)) {
            return 'd' . $value;
        }

        if (\is_string($value)) {
            return '"' . addcslashes($value, '"') . '"';
        }

        if (\is_array($value)) {
            $list = array_is_list($value);
            $hash = '[';

            foreach ($value as $key => $item) {
                if (!$list) {
                    $hash .= $this->hashValue($key);
                }

                $hash .= $this->hashValue($item);
            }

            return $hash . ']';
        }

        if (\is_object($value)) {
            return $this->objectHashes[$value] ??= $this->hashObject($value);
        }

        throw new \RuntimeException();
    }

    private function hashObject(object $value): string
    {
        $objectNormalizer = $this->objectNormalizerFor($value::class);

        if ($objectNormalizer === false) {
            return '(' . serialize($value) . ')';
        }

        return $value::class . '{' . $this->hashValue($objectNormalizer($value)) . '}';
    }

    /**
     * @param class-string $class
     */
    private function objectNormalizerFor(string $class): false|callable
    {
        if (isset($this->objectNormalizers[$class])) {
            return $this->objectNormalizers[$class];
        }

        foreach (class_parents($class) as $parent) {
            if (isset($this->objectNormalizers[$parent])) {
                return $this->objectNormalizers[$class] = $this->objectNormalizers[$parent];
            }
        }

        foreach (class_implements($class) as $interface) {
            if (isset($this->objectNormalizers[$interface])) {
                return $this->objectNormalizers[$class] = $this->objectNormalizers[$interface];
            }
        }

        return $this->objectNormalizers[$class] = false;
    }
}
