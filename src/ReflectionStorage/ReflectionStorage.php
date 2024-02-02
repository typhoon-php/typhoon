<?php

declare(strict_types=1);

namespace Typhoon\Reflection\ReflectionStorage;

use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\ReflectionException;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectionStorage
{
    /**
     * @var array<non-empty-string, false|Reflection>
     */
    private array $reflections = [];

    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private readonly bool $detectChanges = true,
    ) {}

    /**
     * @param class-string $class
     * @param non-empty-string $name
     * @param \Closure(): void $loader
     */
    public function exists(string $class, string $name, \Closure $loader): bool
    {
        $key = $this->key($class, $name);

        if (isset($this->reflections[$key])) {
            return $this->reflections[$key] !== false;
        }

        $cachedReflection = $this->cacheGet($key, $class);

        if ($cachedReflection !== null) {
            $this->reflections[$key] = $cachedReflection;

            return true;
        }

        $this->reflections[$key] = false;

        $loader();

        return $this->reflections[$key] !== false;
    }

    /**
     * @template TReflection of object
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @param \Closure(): void $loader
     * @return TReflection
     */
    public function get(string $class, string $name, \Closure $loader): object
    {
        $key = $this->key($class, $name);

        /** @var null|false|Reflection<TReflection> */
        $reflection = $this->reflections[$key] ?? null;

        if ($reflection === false) {
            throw new ReflectionException(sprintf('%s with name %s not found.', $class, $name));
        }

        if ($reflection !== null) {
            $this->ensureCached($key, $reflection);

            return $reflection->get();
        }

        $cachedReflection = $this->cacheGet($key, $class);

        if ($cachedReflection !== null) {
            $this->reflections[$key] = $cachedReflection;

            return $cachedReflection->get();
        }

        $this->reflections[$key] = false;

        $loader();

        return $this->get($class, $name, static fn(): never => throw new ReflectionException(sprintf('%s with name %s not found.', $class, $name)));
    }

    /**
     * @template TReflection of object
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @param \Closure(): TReflection $reflector
     */
    public function setReflector(string $class, string $name, \Closure $reflector, ChangeDetector $changeDetector): void
    {
        $this->reflections[$this->key($class, $name)] = new Reflection($reflector, $changeDetector);
    }

    /**
     * @template TReflection of object
     * @param non-empty-string $key
     * @param class-string<TReflection> $class
     * @return ?Reflection<TReflection>
     */
    private function cacheGet(string $key, string $class): ?Reflection
    {
        if ($this->cache === null) {
            return null;
        }

        $reflection = $this->cache->get($key);

        if (!$reflection instanceof Reflection) {
            return null;
        }

        $reflection->cached = true;

        if (!$reflection->get() instanceof $class) {
            return null;
        }

        if ($this->detectChanges && $reflection->changed()) {
            return null;
        }

        /** @var Reflection<TReflection> */
        return $reflection;
    }

    /**
     * @param non-empty-string $key
     */
    private function ensureCached(string $key, Reflection $reflection): void
    {
        if ($this->cache === null || $reflection->cached) {
            return;
        }

        $this->cache->set($key, $reflection);
        $reflection->cached = true;
    }

    /**
     * @param class-string $class
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function key(string $class, string $name): string
    {
        return hash('xxh128', $class . '#' . AnonymousClassName::normalizeName($name));
    }
}
