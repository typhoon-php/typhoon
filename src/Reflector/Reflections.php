<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @implements \IteratorAggregate<non-empty-string, RootReflection>
 */
final class Reflections implements \IteratorAggregate
{
    /**
     * @var array<class-string<RootReflection>, array<non-empty-string, bool|RootReflection|\Closure(): RootReflection>>
     */
    private array $reflections = [];

    /**
     * @param class-string<RootReflection> $class
     * @param non-empty-string $name
     */
    public function exists(string $class, string $name): ?bool
    {
        if (isset($this->reflections[$class][$name])) {
            return $this->reflections[$class][$name] !== false;
        }

        return null;
    }

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @return ?TReflection
     */
    public function get(string $class, string $name): ?RootReflection
    {
        $reflection = $this->reflections[$class][$name] ?? null;

        if ($reflection instanceof RootReflection) {
            /** @var TReflection */
            return $reflection;
        }

        if ($reflection instanceof \Closure) {
            /** @var TReflection */
            return $reflection();
        }

        return null;
    }

    /**
     * @param class-string<RootReflection> $class
     * @param non-empty-string $name
     */
    public function setExists(string $class, string $name, bool $exists): void
    {
        $this->reflections[$class][$name] = $exists;
    }

    public function set(RootReflection $reflection): void
    {
        $this->reflections[$reflection::class][$reflection->getName()] = $reflection;
    }

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @param callable(): TReflection $reflectionLoader
     */
    public function setLazy(string $class, string $name, callable $reflectionLoader): void
    {
        $this->reflections[$class][$name] = static function () use ($reflectionLoader): RootReflection {
            /** @var ?RootReflection */
            static $reflection = null;

            return $reflection ??= $reflectionLoader();
        };
    }

    public function setFrom(self $reflections): void
    {
        foreach ($reflections->reflections as $class => $reflectionsByName) {
            foreach ($reflectionsByName as $name => $reflection) {
                $this->reflections[$class][$name] = $reflection;
            }
        }
    }

    /**
     * @return \Generator<non-empty-string, RootReflection>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->reflections as $reflectionsByName) {
            foreach ($reflectionsByName as $name => $reflection) {
                if ($reflection instanceof RootReflection) {
                    yield $name => $reflection;
                } elseif ($reflection instanceof \Closure) {
                    yield $name => $reflection();
                }
            }
        }
    }
}
