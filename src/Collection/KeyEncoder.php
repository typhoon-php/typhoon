<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @internal
 * @psalm-internal Typhoon\Collection
 */
final class KeyEncoder
{
    private static ?self $instance = null;

    private bool $locked = false;

    /**
     * @var \Closure(object): non-empty-string
     */
    private readonly \Closure $defaultObjectNormalizer;

    /**
     * @var array<class-string, callable>
     */
    private array $objectNormalizer;

    private function __construct()
    {
        $this->defaultObjectNormalizer = static fn(object $object): string => '#' . spl_object_id($object);
        $this->objectNormalizer = [
            \DateTimeInterface::class => static fn(\DateTimeInterface $object): string => 'd' . $object->format('YmdHisue'),
        ];
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T): mixed $normalizer
     */
    public static function registerObjectNormalizer(string $class, callable $normalizer): void
    {
        $keyEncoder = self::$instance ??= new self();

        if ($keyEncoder->locked) {
            throw new \LogicException(\sprintf('Please register all normalizers before using %s', Collection::class));
        }

        $keyEncoder->objectNormalizer[$class] =
            /** @param T $object */
            static fn(object $object): string => '@' . $keyEncoder->encodeValue($normalizer($object));
    }

    /**
     * @return int|non-empty-string
     */
    public static function encode(mixed $key): int|string
    {
        $keyEncoder = self::$instance ??= new self();
        $keyEncoder->locked = true;

        return $keyEncoder->encodeValue($key);
    }

    /**
     * @return int|non-empty-string
     */
    private function encodeValue(mixed $value): int|string
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            return '`' . addcslashes($value, '`') . '`';
        }

        if (\is_object($value)) {
            /** @var non-empty-string */
            return $this->getObjectNormalizer($value::class)($value);
        }

        if ($value === null) {
            return 'n';
        }

        if ($value === true) {
            return 't';
        }

        if ($value === false) {
            return 'f';
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        if (\is_array($value)) {
            $encoded = '[';

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    $encoded .= $this->encodeValue($item) . ',';
                }
            } else {
                foreach ($value as $key => $item) {
                    $encoded .= $this->encodeValue($key) . ':' . $this->encodeValue($item) . ',';
                }
            }

            return $encoded . ']';
        }

        if (\is_resource($value)) {
            return 'r' . get_resource_id($value);
        }

        throw new \LogicException(\sprintf('Type %s is not supported', get_debug_type($value)));
    }

    /**
     * @param class-string $class
     */
    private function getObjectNormalizer(string $class): callable
    {
        if (isset($this->objectNormalizer[$class])) {
            return $this->objectNormalizer[$class];
        }

        foreach (class_parents($class) as $parent) {
            if (isset($this->objectNormalizer[$parent])) {
                return $this->objectNormalizer[$class] = $this->objectNormalizer[$parent];
            }
        }

        foreach (class_implements($class) as $interface) {
            if (isset($this->objectNormalizer[$interface])) {
                return $this->objectNormalizer[$class] = $this->objectNormalizer[$interface];
            }
        }

        return $this->objectNormalizer[$class] = $this->defaultObjectNormalizer;
    }
}
