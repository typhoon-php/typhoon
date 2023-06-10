<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Reflector;

use ExtendedTypeSystem\Reflection\Reflection;

/**
 * @implements \IteratorAggregate<non-empty-string, Reflection>
 */
final class Reflections implements \IteratorAggregate
{
    /**
     * @var array<class-string<Reflection>, array<non-empty-string, Reflection|callable(): Reflection>>
     */
    private array $reflections = [];

    /**
     * @param non-empty-string $name
     */
    public function add(string $name, Reflection $reflection): void
    {
        $this->reflections[$reflection::class][$name] = $reflection;
    }

    /**
     * @template TReflection of Reflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @param callable(): TReflection $reflection
     */
    public function addLazy(string $class, string $name, callable $reflection): void
    {
        $this->reflections[$class][$name] = $reflection;
    }

    public function addFrom(self $reflections): void
    {
        foreach ($reflections->reflections as $class => $reflectionsByName) {
            foreach ($reflectionsByName as $name => $reflection) {
                $this->reflections[$class][$name] = $reflection;
            }
        }
    }

    /**
     * @param class-string<Reflection> $class
     * @param non-empty-string $name
     */
    public function has(string $class, string $name): bool
    {
        return isset($this->reflections[$class][$name]);
    }

    /**
     * @template TReflection of Reflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @return ?TReflection
     */
    public function get(string $class, string $name): ?Reflection
    {
        if (!isset($this->reflections[$class][$name])) {
            return null;
        }

        if ($this->reflections[$class][$name] instanceof Reflection) {
            /** @var TReflection */
            return $this->reflections[$class][$name];
        }

        /** @var TReflection */
        return $this->reflections[$class][$name] = $this->reflections[$class][$name]();
    }

    /**
     * @return \Generator<non-empty-string, Reflection>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->reflections as &$reflectionsByName) {
            foreach ($reflectionsByName as $name => &$reflection) {
                if (!$reflection instanceof Reflection) {
                    $reflection = $reflection();
                }

                yield $name => $reflection;
            }
        }
    }
}
