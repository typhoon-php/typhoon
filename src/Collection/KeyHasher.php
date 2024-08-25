<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @internal
 * @psalm-internal Typhoon\Collection
 */
final class KeyHasher
{
    private static ?self $instance = null;

    private bool $locked = false;

    /**
     * @var \Closure(object): int
     */
    private readonly \Closure $defaultObjectHasher;

    /**
     * @var array<class-string, callable>
     */
    private array $objectHashers;

    private function __construct()
    {
        $this->defaultObjectHasher = static fn(object $object): int => -spl_object_id($object) - 4;
        $this->objectHashers = [
            \DateTimeInterface::class => static fn(\DateTimeInterface $object): string => 'd' . $object->format('YmdHisue'),
            \UnitEnum::class => static fn(\UnitEnum $object): string => $object::class . '::' . $object->name,
        ];
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T): non-empty-string $hasher
     */
    public static function registerObjectHasher(string $class, callable $hasher): void
    {
        self::$instance ??= new self();

        if (self::$instance->locked) {
            throw new \LogicException(\sprintf('Please register all normalizers before calling %s::hash()', self::class));
        }

        self::$instance->objectHashers[$class] = $hasher;
    }

    /**
     * @return int|non-empty-string
     */
    public static function hash(mixed $key): int|string
    {
        self::$instance ??= new self();
        self::$instance->locked = true;

        return self::$instance->doHash($key);
    }

    /**
     * @return int|non-empty-string
     */
    private function doHash(mixed $value): int|string
    {
        if (\is_int($value)) {
            if ($value >= 0) {
                return $value;
            }

            return '_' . -$value;
        }

        if (\is_string($value)) {
            return '`' . addcslashes($value, '`') . '`';
        }

        if (\is_object($value)) {
            /** @var int|non-empty-string */
            return $this->objectHasher($value::class)($value);
        }

        if ($value === null) {
            return -1;
        }

        if ($value === true) {
            return -2;
        }

        if ($value === false) {
            return -3;
        }

        if (\is_float($value)) {
            return (string) $value;
        }

        if (\is_array($value)) {
            $hash = '[';

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    $hash .= $this->doHash($item) . ',';
                }
            } else {
                foreach ($value as $key => $item) {
                    $hash .= $this->doHash($key) . ':' . $this->doHash($item) . ',';
                }
            }

            return $hash . ']';
        }

        if (\is_resource($value)) {
            return 'r' . get_resource_id($value);
        }

        throw new \LogicException(\sprintf('Type %s is not supported', get_debug_type($value)));
    }

    /**
     * @param class-string $class
     */
    private function objectHasher(string $class): callable
    {
        if (isset($this->objectHashers[$class])) {
            return $this->objectHashers[$class];
        }

        foreach (class_parents($class) as $parent) {
            if (isset($this->objectHashers[$parent])) {
                return $this->objectHashers[$class] = $this->objectHashers[$parent];
            }
        }

        foreach (class_implements($class) as $interface) {
            if (isset($this->objectHashers[$interface])) {
                return $this->objectHashers[$class] = $this->objectHashers[$interface];
            }
        }

        return $this->objectHashers[$class] = $this->defaultObjectHasher;
    }
}
